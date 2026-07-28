<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\ConsultantController;
use App\Http\Controllers\SchoolLevelProcessController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SchoolBundleController;
use App\Http\Controllers\BundleController;
use App\Http\Controllers\BundleResurtidoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\ComunicadoController;
use App\Http\Controllers\HerramientasController;
use App\Http\Controllers\SchoolServiceTypeController;


Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
     ->middleware(['auth', 'verified', 'verificar.acceso'])
     ->name('dashboard');


Route::middleware(['auth', 'verificar.acceso'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Colegios
    Route::get('schools/plantilla',  [SchoolController::class, 'descargarPlantilla'])->name('schools.plantilla');
    Route::post('schools/importar',  [SchoolController::class, 'importarMasivo'])->name('schools.importar');
    Route::delete('schools-borrar-todo', [SchoolController::class, 'destroyAll'])->name('schools.destroy-all');
    Route::resource('schools', SchoolController::class);

    // Docentes
    Route::resource('schools.teachers', TeacherController::class)
        ->except(['show'])
        ->shallow();
    Route::post('schools/{school}/teachers/import', [TeacherController::class, 'importarMasivo'])
        ->name('schools.teachers.import');
    Route::get('teachers/template', [TeacherController::class, 'downloadTemplate'])
        ->name('teachers.template');

    // Consultores
    Route::resource('consultants', ConsultantController::class);
    Route::patch('consultants/{consultant}/password', [ConsultantController::class, 'updatePassword'])
         ->name('consultants.password');

    // Tickets
    Route::resource('schools.tickets', TicketController::class)
     ->except(['show'])
     ->shallow();    
    
    // Visitas
    Route::resource('schools.visits', VisitController::class)
     ->except(['show'])
     ->shallow();
    
    // Procesos por nivel
    Route::get('schools/{school}/processes', [SchoolLevelProcessController::class, 'index'])
         ->name('schools.processes.index');
    Route::post('schools/{school}/processes/{schoolLevelProcess}', [SchoolLevelProcessController::class, 'update'])
         ->name('schools.processes.update');
    Route::delete('schools/{school}/processes/{schoolLevelProcess}/evidence', [SchoolLevelProcessController::class, 'destroyEvidence'])
         ->name('schools.processes.evidence.destroy');

    // Alumnos
    Route::resource('schools.students', StudentController::class)
        ->except(['show'])
         ->shallow();
    //Eliminar todos los alumnos de un colegio     
    Route::delete('schools/{school}/students/destroy-all', [StudentController::class, 'destroyAll'])
     ->name('schools.students.destroy-all');

    // Carga masiva PDF
    Route::post('schools/{school}/students/upload-pdf', [StudentController::class, 'uploadPdf'])
        ->name('schools.students.upload-pdf');
    Route::post('schools/{school}/students/import-excel', [StudentController::class, 'importarExcel'])
        ->name('schools.students.import-excel');
    
    // Bundles por colegio
    Route::get('schools/{school}/bundles', [SchoolBundleController::class, 'index'])
         ->name('schools.bundles.index');
    Route::post('schools/{school}/bundles', [SchoolBundleController::class, 'store'])
         ->name('schools.bundles.store');
    Route::post('schools/{school}/bundles/import', [SchoolBundleController::class, 'importarMasivo'])
         ->name('schools.bundles.import');
    Route::delete('schools/{school}/bundles/{bundle}', [SchoolBundleController::class, 'destroy'])
         ->name('schools.bundles.destroy');
    Route::delete('schools/{school}/bundles', [SchoolBundleController::class, 'destroyAll'])
         ->name('schools.bundles.destroy-all');
    Route::post('schools/{school}/bundles/{bundle}/resurtido', [BundleResurtidoController::class, 'store'])
         ->name('schools.bundles.resurtido');
    Route::get('api/bundles-by-series', [SchoolBundleController::class, 'getBundlesBySeries'])
        ->name('api.bundles.by.series');
     
     // Bundles catálogo
     Route::post('bundles/importar',  [BundleController::class, 'importar']) ->name('bundles.importar');
     Route::get('bundles/plantilla',  [BundleController::class, 'plantilla'])->name('bundles.plantilla');
     Route::resource('bundles', BundleController::class)->only(['index', 'create', 'store', 'destroy', 'edit', 'update']);

     // Reportes
     Route::get('reportes/zonas',      [ReporteController::class, 'reporteZonas']      )->name('reportes.zonas');
     Route::get('reportes/general',    [ReporteController::class, 'reporteGeneral']    )->name('reportes.general');
     Route::get('reportes/resurtidos', [ReporteController::class, 'reporteResurtidos'] )->name('reportes.resurtidos');
     Route::get('schools/{school}/reporte-master', [ReporteController::class, 'reporteMaster'])->name('schools.reporte-master');

     // Bitácora
     Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');

     // Tablero SI
     Route::get('tablero',                    [ComunicadoController::class, 'index'])  ->name('tablero.index');
     Route::post('tablero',                   [ComunicadoController::class, 'store'])  ->name('tablero.store');
     Route::delete('tablero/{comunicado}',    [ComunicadoController::class, 'destroy'])->name('tablero.destroy');

     // Herramientas SI
     Route::get('herramientas-si',                        [HerramientasController::class, 'index'])          ->name('herramientas.index');
     Route::get('herramientas-si/altas-bundles',          [HerramientasController::class, 'altasBundles'])   ->name('herramientas.altas-bundles');
     Route::get('herramientas-si/accesos',                [HerramientasController::class, 'accesos'])        ->name('herramientas.accesos');
     Route::get('herramientas-si/consulta-accesos',       [HerramientasController::class, 'consultaAccesos'])->name('herramientas.consulta-accesos');
     Route::get('herramientas-si/borrar-usuarios',        [HerramientasController::class, 'borrarUsuarios'])  ->name('herramientas.borrar-usuarios');
     Route::get('herramientas-si/cambio-usuario',         [HerramientasController::class, 'cambioUsuario'])   ->name('herramientas.cambio-usuario');
     Route::get('herramientas-si/cambio-password',        [HerramientasController::class, 'cambioPassword'])  ->name('herramientas.cambio-password');
     Route::get('herramientas-si/eliminacion-bundles',    [HerramientasController::class, 'eliminacionBundles'])->name('herramientas.eliminacion-bundles');
     Route::get('herramientas-si/template-bienvenida',    [HerramientasController::class, 'templateBienvenida'])->name('herramientas.template-bienvenida');
     Route::get('herramientas-si/template-confirmacion',  [HerramientasController::class, 'templateConfirmacion'])->name('herramientas.template-confirmacion');
     Route::post('herramientas-si/archivos',              [HerramientasController::class, 'store'])          ->name('herramientas.archivos.store');
     Route::post('herramientas-si/archivos/{archivoSI}',  [HerramientasController::class, 'update'])         ->name('herramientas.archivos.update');
     Route::delete('herramientas-si/archivos/{archivoSI}',[HerramientasController::class, 'destroy'])        ->name('herramientas.archivos.destroy');

     // Configuración — servicios contables por colegio (solo admin)
     Route::get('configuracion/servicios',                  [SchoolServiceTypeController::class, 'index'])  ->name('configuracion.servicios.index');
     Route::post('configuracion/servicios',                 [SchoolServiceTypeController::class, 'store'])  ->name('configuracion.servicios.store');
     Route::patch('configuracion/servicios/{type}/toggle',  [SchoolServiceTypeController::class, 'toggle']) ->name('configuracion.servicios.toggle');
     Route::delete('configuracion/servicios/{type}',        [SchoolServiceTypeController::class, 'destroy'])->name('configuracion.servicios.destroy');

     // Tareas SI
     Route::get('tareas', [TareaController::class, 'index'])->name('tareas.index');
     Route::post('tareas', [TareaController::class, 'store'])->name('tareas.store');
     Route::patch('tareas/{tarea}/colegios/{school}', [TareaController::class, 'updateStatus'])->name('tareas.update-status');
     Route::delete('tareas/{tarea}', [TareaController::class, 'destroy'])->name('tareas.destroy');
});






require __DIR__.'/auth.php';
