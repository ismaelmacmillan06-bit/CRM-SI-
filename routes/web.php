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
use App\Http\Controllers\DashboardController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
     ->middleware(['auth', 'verified'])
     ->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Colegios
    Route::resource('schools', SchoolController::class);

    // Docentes
    Route::resource('schools.teachers', TeacherController::class)
     ->except(['show'])
     ->shallow();

    // Consultores
    Route::resource('consultants', ConsultantController::class);

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
    
    // Bundles por colegio
    Route::get('schools/{school}/bundles', [SchoolBundleController::class, 'index'])
         ->name('schools.bundles.index');
    Route::post('schools/{school}/bundles', [SchoolBundleController::class, 'store'])
         ->name('schools.bundles.store');
    Route::delete('schools/{school}/bundles/{bundle}', [SchoolBundleController::class, 'destroy'])
         ->name('schools.bundles.destroy');
    Route::get('api/bundles-by-series', [SchoolBundleController::class, 'getBundlesBySeries'])
        ->name('api.bundles.by.series');
     
     // Bundles catálogo
     Route::resource('bundles', BundleController::class)->only(['index', 'create', 'store', 'destroy']);
});






require __DIR__.'/auth.php';
