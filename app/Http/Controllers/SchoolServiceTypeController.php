<?php

namespace App\Http\Controllers;

use App\Models\Process;
use App\Models\SchoolServiceType;
use App\Models\SeguimientoDefinicionAccion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolServiceTypeController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $types = SchoolServiceType::orderBy('order')->get();
        $plataformas = SeguimientoController::PLATAFORMAS;
        $totalProcesosGlobales = Process::count();
        $rawSegCounts = SeguimientoDefinicionAccion::selectRaw('plataforma, COUNT(*) as total')
            ->groupBy('plataforma')
            ->pluck('total', 'plataforma');
        $conteoSeg = [];
        foreach (array_keys($plataformas) as $slug) {
            $conteoSeg[$slug] = (int) ($rawSegCounts[$slug] ?? 0);
        }

        return view('configuracion.servicios', compact('types', 'plataformas', 'totalProcesosGlobales', 'conteoSeg'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $request->validate([
            'name'  => 'required|string|max:100|unique:school_service_types,name',
            'icon'  => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
            'plataforma' => $this->reglaPlataforma(),
        ], $this->mensajes());

        $maxOrder = SchoolServiceType::max('order') ?? 0;
        SchoolServiceType::create([
            'name'  => $request->name,
            'icon'  => $request->icon  ?: '📦',
            'color' => $request->color ?: '#6366f1',
            'order' => $maxOrder + 1,
            'active'=> true,
            'plataforma' => $request->plataforma ?: null,
        ]);

        return back()->with('success', "Servicio \"{$request->name}\" creado correctamente.");
    }

    public function update(Request $request, SchoolServiceType $type)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $request->merge(['name' => trim((string) $request->name)]);
        $request->validateWithBag('editar_' . $type->id, [
            'name'  => 'required|string|max:100|unique:school_service_types,name,' . $type->id,
            'icon'  => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
            'plataforma' => $this->reglaPlataforma($type),
        ], $this->mensajes());

        $anterior = $type->name;
        $type->update([
            'name'  => $request->name,
            'icon'  => $request->icon  ?: $type->icon,
            'color' => $request->color ?: $type->color,
            'plataforma' => $request->plataforma ?: null,
        ]);

        $msg = $anterior !== $type->name
            ? "Servicio \"{$anterior}\" renombrado a \"{$type->name}\"."
            : "Servicio \"{$type->name}\" actualizado.";
        return back()->with('success', $msg);
    }

    private function reglaPlataforma(?SchoolServiceType $type = null): array
    {
        return [
            'nullable',
            Rule::in(array_keys(SeguimientoController::PLATAFORMAS)),
            Rule::unique('school_service_types', 'plataforma')->ignore($type?->id),
        ];
    }

    private function mensajes(): array
    {
        return [
            'name.unique'       => 'Ya existe otro servicio con ese nombre.',
            'plataforma.unique' => 'Esa plataforma ya está ligada a otro servicio.',
        ];
    }

    public function toggle(SchoolServiceType $type)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $type->update(['active' => !$type->active]);
        return back()->with('success', "Servicio actualizado.");
    }

    public function destroy(SchoolServiceType $type)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $type->schools()->detach();
        $type->delete();
        return back()->with('success', "Servicio \"{$type->name}\" eliminado.");
    }
}
