<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentExportByClassTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private School $otherSchool;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'admin']));
        $consultant = Consultant::create(['user_id' => $user->id]);
        $this->school = School::create(['name' => 'Colegio Prueba', 'status' => 'activo', 'consultant_id' => $consultant->id]);
        $this->otherSchool = School::create(['name' => 'Colegio Ajeno', 'status' => 'activo', 'consultant_id' => $consultant->id]);
        $this->actingAs($user);
    }

    public function test_index_detects_standard_and_custom_classes_including_over_twenty(): void
    {
        $grupos = [
            ['Primaria', '1A'],
            ['Preescolar', 'azul'],
            ['Preescolar', 'verde'],
            ['Secundaria', 'intermedio'],
        ];
        foreach (range(1, 20) as $i) {
            $grupos[] = ['Primaria', "Grupo {$i}"];
        }

        foreach ($grupos as $idx => [$level, $grade]) {
            $this->school->students()->create([
                'name'         => "Alumno{$idx}",
                'last_name'    => 'Test',
                'mee_username' => "alumno{$idx}@test.com",
                'mee_password' => 'pass123',
                'level'        => $level,
                'grade'        => $grade,
            ]);
        }

        $response = $this->get(route('schools.students.index', $this->school));

        $response->assertOk();
        $response->assertSee('24 clases detectadas');
        $response->assertSee('Primaria · 1A');
        $response->assertSee('Preescolar · azul');
        $response->assertSee('Preescolar · verde');
        $response->assertSee('Secundaria · intermedio');
        $response->assertSee('Primaria · Grupo 20');
    }

    public function test_export_by_class_downloads_selected_classes_as_sheets(): void
    {
        $this->school->students()->create([
            'name' => 'Sofía', 'last_name' => 'López',
            'mee_username' => 'sofia@test.com', 'mee_password' => '00123',
            'level' => 'Primaria', 'grade' => 'azul',
        ]);
        $this->school->students()->create([
            'name' => 'Mateo', 'last_name' => 'Ruiz',
            'mee_username' => 'mateo@test.com', 'mee_password' => 'abc99',
            'level' => 'Secundaria', 'grade' => 'intermedio',
        ]);
        $this->school->students()->create([
            'name' => 'NoIncluido', 'last_name' => 'Pérez',
            'mee_username' => 'noincluido@test.com', 'mee_password' => 'zzz',
            'level' => 'Primaria', 'grade' => 'verde',
        ]);
        $this->otherSchool->students()->create([
            'name' => 'Ajeno', 'last_name' => 'Otro',
            'mee_username' => 'ajeno@test.com', 'mee_password' => 'xxx',
            'level' => 'Primaria', 'grade' => 'azul',
        ]);

        $keyAzul = base64_encode(json_encode(['Primaria', 'azul']));
        $keyInter = base64_encode(json_encode(['Secundaria', 'intermedio']));

        $response = $this->post(route('schools.students.export-by-class', $this->school), [
            'clases' => [$keyAzul, $keyInter],
            'modo'   => 'hojas',
        ]);

        $response->assertOk();
        $file = $response->getFile()->getPathname();
        $spreadsheet = IOFactory::load($file);

        $this->assertSame(2, $spreadsheet->getSheetCount());
        $sheetNames = $spreadsheet->getSheetNames();
        $this->assertContains('Primaria · azul', $sheetNames);
        $this->assertContains('Secundaria · intermedio', $sheetNames);

        $azulSheet = $spreadsheet->getSheetByName('Primaria · azul');
        $this->assertStringContainsString('COLEGIO PRUEBA', (string) $azulSheet->getCell('A1')->getValue());
        $this->assertCount(1, $azulSheet->getDrawingCollection());
        $this->assertSame('Sofía', $azulSheet->getCell('A6')->getValue());
        $this->assertSame('00123', $azulSheet->getCell('F6')->getValue());
        $this->assertNull($azulSheet->getCell('A7')->getValue());
    }

    public function test_export_by_class_unified_mode_puts_all_selected_in_one_sheet(): void
    {
        $this->school->students()->create([
            'name' => 'Ana', 'last_name' => 'Gómez',
            'mee_username' => 'ana@test.com', 'mee_password' => 'p1',
            'level' => 'Preescolar', 'grade' => 'verde',
        ]);
        $this->school->students()->create([
            'name' => 'Luis', 'last_name' => 'Díaz',
            'mee_username' => 'luis@test.com', 'mee_password' => 'p2',
            'level' => 'Primaria', 'grade' => '1A',
        ]);

        $response = $this->post(route('schools.students.export-by-class', $this->school), [
            'clases' => [
                base64_encode(json_encode(['Preescolar', 'verde'])),
                base64_encode(json_encode(['Primaria', '1A'])),
            ],
            'modo' => 'unica',
        ]);

        $response->assertOk();
        $spreadsheet = IOFactory::load($response->getFile()->getPathname());
        $this->assertSame(1, $spreadsheet->getSheetCount());
        $sheet = $spreadsheet->getActiveSheet();
        // Grupo 1 (Preescolar · verde): fila 4 título clase, fila 5 headers, fila 6 alumno
        $this->assertSame('Ana', $sheet->getCell('A6')->getValue());
        // Fila 7 separador, fila 8 título clase 2 (Primaria · 1A), fila 9 headers, fila 10 alumno
        $this->assertSame('Luis', $sheet->getCell('A10')->getValue());
    }

    public function test_export_by_class_requires_at_least_one_class(): void
    {
        $response = $this->post(route('schools.students.export-by-class', $this->school), [
            'clases' => [],
        ]);

        $response->assertSessionHasErrors('clases');
    }
}
