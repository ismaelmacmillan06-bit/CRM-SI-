<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    public function index()
    {
        $bundles = Bundle::orderBy('type')->orderBy('serie')->orderBy('level')->orderBy('grade')->get();
        $series  = Bundle::select('serie', 'type')->distinct()->orderBy('type')->orderBy('serie')->get();
        $tipos   = Bundle::select('type')->distinct()->pluck('type');
        return view('bundles.index', compact('bundles', 'series', 'tipos'));
    }

    public function create()
    {
        return view('bundles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'serie' => 'required|string|max:255',
            'name'  => 'required|string|max:255',
            'grade' => 'nullable|string|max:50',
            'level' => 'nullable|string|max:100',
            'role'  => 'required|in:student,teacher',
            'type'  => 'required|in:ELT,Plan Lector,Imagina,Wikids,Pienso Contigo',
        ]);

        Bundle::create($request->all());

        return redirect()->route('bundles.index')
                         ->with('success', 'Bundle registrado correctamente.');
    }

    public function destroy(Bundle $bundle)
    {
        $bundle->delete();
        return redirect()->route('bundles.index')
                         ->with('success', 'Bundle eliminado correctamente.');
    }
}