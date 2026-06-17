<?php

namespace App\Http\Controllers;

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
        return view('consultants.index', compact('consultants'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('consultants.create', compact('roles'));
    }

public function store(Request $request)
{
    $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed',
        'phone'    => 'nullable|string|max:20',
        'zone'     => 'nullable|string|max:100',
        'role'     => 'required|exists:roles,name',
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
    ];

    if ($request->hasFile('photo')) {
        $consultantData['photo'] = $request->file('photo')->store('consultants', 'public');
    }

    Consultant::create($consultantData);

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
        $roles = Role::all();
        return view('consultants.edit', compact('consultant', 'roles'));
    }

    public function update(Request $request, Consultant $consultant)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'zone'  => 'nullable|string|max:100',
            'role'  => 'required|exists:roles,name',
        ]);

        $consultant->user->update(['name' => $request->name]);
        $consultant->user->syncRoles($request->role);
        $consultant->update([
            'phone' => $request->phone,
            'zone'  => $request->zone,
        ]);

        $data = [
    'phone' => $request->phone,
    'zone'  => $request->zone,
];

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

    public function destroy(Consultant $consultant)
    {
        $consultant->user->delete();
        return redirect()->route('consultants.index')
                         ->with('success', 'Consultor eliminado correctamente.');
    }
}