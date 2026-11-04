<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentSearchTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'admin']));
        $consultant = Consultant::create(['user_id' => $user->id]);
        $this->school = School::create(['name' => 'Colegio A', 'status' => 'activo', 'consultant_id' => $consultant->id]);
        $this->actingAs($user);

        // 60 alumnos que ordenan antes: el buscado queda en la página 2 (50 por página)
        foreach (range(1, 60) as $i) {
            $this->school->students()->create([
                'name' => 'Ana' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'last_name' => 'Primera',
                'mee_username' => "ana{$i}@everest.com",
                'mee_password' => 'secreto',
                'level' => 'Preescolar',
                'grade' => '1°A',
            ]);
        }
        $this->school->students()->create([
            'name' => 'Leonardo',
            'last_name' => 'Peyret Gonzales',
            'mee_username' => 'leonardopg@everest.com',
            'mee_password' => 'secreto',
            'level' => 'Primaria',
            'grade' => '1°A',
        ]);
    }

    private function buscar(string $q)
    {
        return $this->get(route('schools.students.index', ['school' => $this->school, 'q' => $q]));
    }

    public function test_student_outside_first_page_is_listed_only_on_page_two_without_search(): void
    {
        $this->get(route('schools.students.index', $this->school))->assertDontSee('leonardopg@everest.com');
    }

    public function test_search_finds_student_from_another_page(): void
    {
        $this->buscar('leonardopg@everest.com')
            ->assertOk()
            ->assertSee('leonardopg@everest.com')
            ->assertSee('1 resultado')
            ->assertDontSee('ana1@everest.com');
    }

    public function test_search_matches_name_and_surname_in_any_order(): void
    {
        $this->buscar('Gonzales Leonardo')->assertSee('leonardopg@everest.com');
        $this->buscar('leonardo peyret')->assertSee('leonardopg@everest.com');
    }

    public function test_search_only_covers_the_current_school(): void
    {
        $otro = School::create(['name' => 'Colegio B', 'status' => 'activo', 'consultant_id' => $this->school->consultant_id]);
        $otro->students()->create(['name' => 'Oculto', 'last_name' => 'Ajeno', 'mee_username' => 'oculto@otro.com', 'mee_password' => 'secreto']);

        $this->buscar('Oculto')
            ->assertDontSee('Ajeno')
            ->assertSee('No se encontró ningún alumno');
    }

    public function test_wildcards_are_treated_as_plain_text(): void
    {
        $this->buscar('%')->assertSee('No se encontró ningún alumno');
    }

    public function test_results_are_paginated_and_links_keep_the_search(): void
    {
        $this->buscar('Primera')
            ->assertSee('60 resultados')
            ->assertSee('q=Primera', false)
            ->assertSee('page=2', false);
    }
}
