<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\School;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index(School $school)
    {
        $teachers = $school->teachers()->get();
        return view('teachers.index', compact('school', 'teachers'));
    }

    public function create(School $school)
    {
        return view('teachers.create', compact('school'));
    }

    public function store(Request $request, School $school)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email'     => 'nullable|email',
            'grade'     => 'nullable|string|max:100',
        ]);

        $school->teachers()->create($request->all());

        return redirect()->route('schools.teachers.index', $school)
                         ->with('success', 'Docente registrado correctamente.');
    }

    public function edit(Teacher $teacher)
    {
        return view('teachers.edit', compact('teacher'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email'     => 'nullable|email',
            'grade'     => 'nullable|string|max:100',
        ]);

        $teacher->update($request->all());

        return redirect()->route('schools.teachers.index', $teacher->school)
                         ->with('success', 'Docente actualizado correctamente.');
    }

    public function destroy(Teacher $teacher)
    {
        $school = $teacher->school;
        $teacher->delete();
        return redirect()->route('schools.teachers.index', $school)
                         ->with('success', 'Docente eliminado correctamente.');
    }
}