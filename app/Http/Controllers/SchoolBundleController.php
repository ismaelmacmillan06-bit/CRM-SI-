<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BundleResurtido;
use App\Models\School;
use App\Models\Bundle;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SchoolBundleController extends Controller
{
    public function index(School $school)
    {
        $schoolBundles = $school->bundles()->orderBy('type')->orderBy('serie')->get();
        $series = Bundle::select('serie', 'type')->distinct()->orderBy('type')->orderBy('serie')->get();
        $resurtidosPorBundle = BundleResurtido::where('school_id', $school->id)
            ->with('user')
            ->orderBy('fecha', 'desc')
            ->get()
            ->groupBy('bundle_id');
        return view('bundles.school', compact('school', 'schoolBundles', 'series', 'resurtidosPorBundle'));
    }

    public function getBundlesBySeries(Request $request)
    {
        $series = $request->series ?? [];
        $bundles = Bundle::whereIn('serie', $series)
                        ->orderBy('type')
                        ->orderBy('serie')
                        ->orderBy('level')
                        ->orderBy('grade')
                        ->orderBy('role')
                        ->get()
                        ->groupBy('serie');
        return response()->json($bundles);
    }

    public function store(Request $request, School $school)
    {
        $request->validate([
            'bundle_ids'   => 'required|array',
            'bundle_ids.*' => 'exists:bundles,id',
            'acquired_at'  => 'nullable|date',
        ]);

        foreach ($request->bundle_ids as $bundleId) {
            $quantity = $request->quantities[$bundleId] ?? 1;
            // Evitar duplicados
            if (!$school->bundles()->where('bundle_id', $bundleId)->exists()) {
                $school->bundles()->attach($bundleId, [
                    'quantity'    => $quantity,
                    'acquired_at' => $request->acquired_at,
                ]);
            }
        }

        return redirect()->route('schools.bundles.index', $school)
                         ->with('success', 'Bundles agregados correctamente.');
    }

    public function importarMasivo(Request $request, School $school)
    {
        $request->validate([
            'archivo'     => 'required|file|mimes:xlsx,xls,csv|max:20480',
            'acquired_at' => 'nullable|date',
        ]);

        $spreadsheet = IOFactory::load($request->file('archivo')->getPathname());
        $sheet       = $spreadsheet->getActiveSheet();

        // Índice de bundles por nombre normalizado para búsqueda rápida
        $bundleMap = Bundle::all()->keyBy(fn($b) => mb_strtolower(trim($b->name)));

        $fecha        = $request->acquired_at ?? now()->toDateString();
        $importados   = 0;
        $duplicados   = 0;
        $noEncontrados = [];

        foreach ($sheet->getRowIterator(2) as $row) {
            $ri     = $row->getRowIndex();
            $nombre = trim((string) $sheet->getCell('B' . $ri)->getValue());
            $cantidad = (int) $sheet->getCell('D' . $ri)->getValue();

            if ($nombre === '') continue;

            $bundle = $bundleMap[mb_strtolower($nombre)] ?? null;

            if (!$bundle) {
                $noEncontrados[] = $nombre;
                continue;
            }

            if ($school->bundles()->where('bundle_id', $bundle->id)->exists()) {
                $duplicados++;
                continue;
            }

            $school->bundles()->attach($bundle->id, [
                'quantity'    => $cantidad > 0 ? $cantidad : 1,
                'acquired_at' => $fecha,
            ]);
            $importados++;
        }

        $msg = "{$importados} bundle(s) importados correctamente.";
        if ($duplicados > 0) {
            $msg .= " {$duplicados} ya existían y se omitieron.";
        }
        if (!empty($noEncontrados)) {
            $msg .= ' No encontrados en catálogo: ' . implode(', ', array_unique($noEncontrados)) . '.';
        }

        if ($importados > 0) {
            ActivityLog::log('bundle', "Importación masiva: $importados bundle(s) agregados", $school->id, '📚');
        }

        return redirect()->route('schools.bundles.index', $school)->with('success', $msg);
    }

    public function destroy(School $school, Bundle $bundle)
    {
        $school->bundles()->detach($bundle->id);
        return back()->with('success', 'Bundle eliminado correctamente.');
    }
}