<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Consultant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;

class ConsultantController extends Controller
{
    public function index()
    {
        $consultants = Consultant::with('user', 'schoolConsultants')->get();

        $conteoPorRol = $consultants
            ->groupBy(fn($c) => $c->user->getRoleNames()->first() ?? 'sin_rol')
            ->map->count();

        return view('consultants.index', compact('consultants', 'conteoPorRol'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, 'Solo el administrador puede agregar miembros.');
        $roles = Role::all();
        return view('consultants.create', compact('roles'));
    }

public function store(Request $request)
{
    // Crear usuarios implica asignar rol: solo admin
    abort_unless(auth()->user()->hasRole('admin'), 403, 'Solo el administrador puede agregar miembros.');
    $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed',
        'phone'    => 'nullable|string|max:20',
        'zone'     => 'nullable|string|max:100',
        'role'     => 'required|exists:roles,name',
        'photo'    => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        'responsable_seguimiento'   => 'nullable|array',
        'responsable_seguimiento.*' => 'string',
    ]);

    $user = User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($request->password),
    ]);

    $user->assignRole($request->role);

    $consultantData = [
        'user_id' => $user->id,
        'phone'   => $request->phone,
        'zone'    => $request->zone,
        'responsable_seguimiento' => $this->responsabilidades($request, $request->role),
    ];

    if ($request->hasFile('photo')) {
        $consultantData['photo'] = $request->file('photo')->store('consultants', 'public');
    }

    Consultant::create($consultantData);

    ActivityLog::log('equipo', "Consultor \"{$request->name}\" registrado", null, '👤');

    return redirect()->route('consultants.index')
                     ->with('success', 'Consultor registrado correctamente.');
}

    public function show(Consultant $consultant)
    {
        $consultant->load('user', 'schools');
        return view('consultants.show', compact('consultant'));
    }

    public function edit(Consultant $consultant)
    {
        $this->autorizarEdicion($consultant);
        $roles = Role::all();
        return view('consultants.edit', compact('consultant', 'roles'));
    }

    public function update(Request $request, Consultant $consultant)
    {
        $this->autorizarEdicion($consultant);
        $isAdmin = auth()->user()->hasRole('admin');

        $rules = [
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'zone'  => 'nullable|string|max:100',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ];

        // Solo admin puede cambiar email y rol
        if ($isAdmin) {
            $rules['email'] = 'required|email|unique:users,email,' . $consultant->user->id;
            $rules['role']  = 'required|exists:roles,name';
            $rules['responsable_seguimiento']   = 'nullable|array';
            $rules['responsable_seguimiento.*'] = 'string';
        }

        $request->validate($rules);

        $userUpdate = ['name' => $request->name];
        if ($isAdmin && $request->filled('email')) {
            $userUpdate['email'] = $request->email;
        }
        $consultant->user->update($userUpdate);

        if ($isAdmin && $request->filled('role')) {
            $consultant->user->syncRoles($request->role);
        }

        $data = ['phone' => $request->phone, 'zone' => $request->zone];

        // Responsable de Seguimiento: solo el admin lo asigna (nadie se lo puede dar a sí mismo)
        if ($isAdmin && $request->has('responsable_seguimiento_enviado')) {
            $antes = (array) ($consultant->responsable_seguimiento ?? []);
            $data['responsable_seguimiento'] = $this->responsabilidades($request, $request->input('role', $consultant->user->getRoleNames()->first()));
            if ($antes != (array) ($data['responsable_seguimiento'] ?? [])) {
                $lista = implode(', ', (array) ($data['responsable_seguimiento'] ?? [])) ?: 'ninguna';
                ActivityLog::log('equipo', "Responsable de Seguimiento de \"{$consultant->user->name}\": {$lista}", null, '🧭');
            }
        }

        if ($request->hasFile('photo')) {
            if ($consultant->photo) {
                Storage::disk('public')->delete($consultant->photo);
            }
            $data['photo'] = $request->file('photo')->store('consultants', 'public');
        }

        $consultant->update($data);

        return redirect()->route('consultants.index')
                         ->with('success', 'Consultor actualizado correctamente.');
    }

    /** Plataformas válidas marcadas; solo se guardan para consultores digitales. */
    private function responsabilidades(Request $request, ?string $rol): ?array
    {
        if ($rol !== 'consultor_digital') {
            return null;
        }
        $validas = array_keys(\App\Http\Controllers\SeguimientoController::PLATAFORMAS);
        $marcadas = array_values(array_intersect($validas, (array) $request->input('responsable_seguimiento', [])));

        return $marcadas ?: null;
    }

    public function updatePassword(Request $request, Consultant $consultant)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required'  => 'La nueva contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $consultant->user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('consultants.edit', $consultant)
                         ->with('success_password', 'Contraseña actualizada correctamente.');
    }

    public function destroy(Consultant $consultant)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, 'Solo el administrador puede eliminar miembros.');
        $nombre = $consultant->user?->name ?? 'Consultor';
        $consultant->user?->delete();
        ActivityLog::log('equipo', "Consultor \"{$nombre}\" eliminado", null, '🗑️');
        return redirect()->route('consultants.index')
                         ->with('success', 'Consultor eliminado correctamente.');
    }

    /**
     * Admin edita a cualquiera (incluido rol y correo). El resto solo su propia ficha
     * (nombre, teléfono, zona, foto); el rol y el correo los cambia únicamente el admin.
     */
    private function autorizarEdicion(Consultant $consultant): void
    {
        $user = auth()->user();
        abort_unless(
            $user->hasRole('admin') || (int) $consultant->user_id === (int) $user->id,
            403,
            'Solo el administrador puede editar a otros miembros del equipo.'
        );
    }
}
