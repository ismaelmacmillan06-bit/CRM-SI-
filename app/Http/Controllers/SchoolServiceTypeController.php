<?php

namespace App\Http\Controllers;

use App\Models\SchoolServiceType;
use Illuminate\Http\Request;

class SchoolServiceTypeController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $types = SchoolServiceType::orderBy('order')->get();
        return view('configuracion.servicios', compact('types'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $request->validate([
            'name'  => 'required|string|max:100|unique:school_service_types,name',
            'icon'  => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
        ]);

        $maxOrder = SchoolServiceType::max('order') ?? 0;
        SchoolServiceType::create([
            'name'  => $request->name,
            'icon'  => $request->icon  ?: '📦',
            'color' => $request->color ?: '#6366f1',
            'order' => $maxOrder + 1,
            'active'=> true,
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
        ], [
            'name.unique' => 'Ya existe otro servicio con ese nombre.',
        ]);

        $anterior = $type->name;
        $type->update([
            'name'  => $request->name,
            'icon'  => $request->icon  ?: $type->icon,
            'color' => $request->color ?: $type->color,
        ]);

        $msg = $anterior !== $type->name
            ? "Servicio \"{$anterior}\" renombrado a \"{$type->name}\"."
            : "Servicio \"{$type->name}\" actualizado.";
        return back()->with('success', $msg);
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
