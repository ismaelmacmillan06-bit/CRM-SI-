<?php

namespace Tests\Feature;

use App\Http\Controllers\FormatoEntregaController;
use App\Http\Controllers\SchoolController;
use App\Models\Consultant;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class FormatoEntregaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_current_students_by_class_with_the_delivery_layout(): void
    {
        $user = User::factory()->create();
        $consultant = Consultant::create(['user_id' => $user->id]);
        $school = School::create([
            'name' => 'Colegio de prueba',
            'status' => 'activo',
            'consultant_id' => $consultant->id,
        ]);
        $formatController = app(FormatoEntregaController::class);

        Student::create([
            'school_id' => $school->id,
            'name' => 'Maria Elena',
            'last_name' => 'De la Cruz Perez',
            'mee_username' => 'maria@example.test',
            'mee_password' => '00123',
            'level' => 'Primaria',
            'grade' => '1°A',
        ]);
        Student::create([
            'school_id' => $school->id,
            'name' => 'Alex',
            'last_name' => 'Ortega y Gasset Ruiz',
            'mee_username' => 'alex.usuario.con.nombre.extenso@example.test',
            'mee_password' => 'clave-2',
            'level' => 'Primaria',
            'grade' => '1°A',
        ]);
        $genderSamples = [
            ['Samadhi Valentina', 'samadhi'],
            ['Joe Eduardo', 'joe'],
            ['Eithan Gael', 'eithan'],
            ['Ander Matias', 'ander'],
            ['Allison Michelle', 'allison'],
            ['Zuri Abril', 'zuri'],
            ['Khaled', 'khaled'],
            ['Itzae', 'itzae'],
            ['Iktan', 'iktan'],
            ['Ainhoa', 'ainhoa'],
            ['Antoine', 'antoine'],
            ['Madison', 'madison'],
            ['Oliver', 'oliver'],
            ['Yareli', 'yareli'],
            ['Quentin', 'quentin'],
        ];
        foreach ($genderSamples as [$name, $username]) {
            Student::create([
                'school_id' => $school->id,
                'name' => $name,
                'last_name' => 'Apellido de prueba ' . $username,
                'mee_username' => $username . '@example.test',
                'mee_password' => 'clave',
                'level' => 'Primaria',
                'grade' => '1°A',
            ]);
        }

        $summary = $formatController->summaryForSchool($school);
        $this->assertSame(1, $summary['reviewCount']);
        $this->assertSame(1, $summary['classCount']);
        $this->assertSame(17, $summary['studentCount']);
        $this->assertSame(0, $summary['teacherCount']);

        $this->actingAs($user);
        $schoolPage = app(SchoolController::class)->show($school, $formatController)->render();
        $normalizedSchoolPage = preg_replace('/\s+/', ' ', $schoolPage);
        $this->assertStringContainsString('style="display:none;', $normalizedSchoolPage);
        $this->assertStringContainsString('data-persistent', $normalizedSchoolPage);
        $this->assertStringContainsString('data-student-review-count="1"', $normalizedSchoolPage);
        $this->assertStringContainsString('data-student-class-count="1"', $normalizedSchoolPage);
        $this->assertStringContainsString('data-teacher-review-count="0"', $normalizedSchoolPage);
        $this->assertStringContainsString('data-teacher-class-count="0"', $normalizedSchoolPage);
        $this->assertStringContainsString('data-student-count="17"', $normalizedSchoolPage);
        $this->assertStringContainsString('data-teacher-count="0"', $normalizedSchoolPage);
        $this->assertStringContainsString('id="formato-entrega-overlay"', $schoolPage);
        $this->assertStringContainsString('formato-loading-spinner', $schoolPage);
        $this->assertStringContainsString('.formato-modal-panel [hidden] { display:none !important; }', $schoolPage);
        $this->assertStringContainsString('value="alumnos"', $normalizedSchoolPage);
        $this->assertStringContainsString('value="docentes"', $normalizedSchoolPage);
        $this->assertStringContainsString('value="ambos"', $normalizedSchoolPage);
        $this->assertStringContainsString('position:fixed; inset:0', $normalizedSchoolPage);
        $this->assertStringContainsString(route('schools.formato-entrega.script', $school), $schoolPage);

        $scriptResponse = $formatController->script($school);
        $this->assertSame('application/javascript; charset=UTF-8', $scriptResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('button.addEventListener', $scriptResponse->getContent());
        $this->assertStringContainsString("['alumnos', 'docentes']", $scriptResponse->getContent());
        $this->assertStringContainsString('showNotice(', $scriptResponse->getContent());
        $this->assertStringNotContainsString('window.confirm', $scriptResponse->getContent());
        $this->assertStringNotContainsString('window.alert', $scriptResponse->getContent());
        $this->assertStringContainsString('showLoading()', $scriptResponse->getContent());

        $response = $formatController->download($school, Request::create('/', 'GET'));
        $filePath = $response->getFile()->getPathname();

        try {
            $this->assertSame('1', $response->headers->get('X-Alumnos-Por-Revisar'));
            $this->assertSame('1', $response->headers->get('X-Clases-Alumnos'));
            $this->assertStringContainsString(
                'Cuentas de Alumno_Colegio de prueba_1.xlsx',
                urldecode($response->headers->get('Content-Disposition')),
            );
            $spreadsheet = IOFactory::load($filePath);
            $this->assertSame(['Todos los alumnos', 'Primaria 1°A'], $spreadsheet->getSheetNames());

            $summary = $spreadsheet->getSheet(0);
            $this->assertSame('Colegio de prueba', $summary->getCell('C2')->getValue());
            $this->assertCount(1, $summary->getDrawingCollection());
            $this->assertFileExists(public_path('images/logo-formato-entrega.png'));
            $this->assertSame(24.0, $summary->getRowDimension(7)->getRowHeight());
            $this->assertGreaterThan(22, $summary->getColumnDimension('D')->getWidth());

            $rowsByUsername = [];
            for ($row = 7; $row <= 8 + count($genderSamples); $row++) {
                $rowsByUsername[$summary->getCell("D{$row}")->getValue()] = $row;
            }

            $mariaRow = $rowsByUsername['maria@example.test'];
            $this->assertSame('De la Cruz', $summary->getCell("B{$mariaRow}")->getValue());
            $this->assertSame('Perez', $summary->getCell("C{$mariaRow}")->getValue());
            $this->assertSame('A', $summary->getCell("H{$mariaRow}")->getValue());
            $this->assertSame('Femenino', $summary->getCell("I{$mariaRow}")->getValue());
            $this->assertSame('00123', $summary->getCell("E{$mariaRow}")->getValue());
            $this->assertSame('s', $summary->getCell("E{$mariaRow}")->getDataType());
            $alexRow = $rowsByUsername['alex.usuario.con.nombre.extenso@example.test'];
            $this->assertSame('Revisar', $summary->getCell("I{$alexRow}")->getValue());
            foreach ([
                'samadhi' => 'Femenino',
                'joe' => 'Masculino',
                'eithan' => 'Masculino',
                'ander' => 'Masculino',
                'allison' => 'Femenino',
                'zuri' => 'Femenino',
                'khaled' => 'Masculino',
                'itzae' => 'Masculino',
                'iktan' => 'Masculino',
                'ainhoa' => 'Femenino',
                'antoine' => 'Masculino',
                'madison' => 'Femenino',
                'oliver' => 'Masculino',
                'yareli' => 'Femenino',
                'quentin' => 'Masculino',
            ] as $username => $gender) {
                $row = $rowsByUsername[$username . '@example.test'];
                $this->assertSame($gender, $summary->getCell("I{$row}")->getValue());
            }
        } finally {
            @unlink($filePath);
        }
    }

    public function test_it_rejects_exports_when_the_selected_account_type_has_no_records(): void
    {
        $user = User::factory()->create();
        $consultant = Consultant::create(['user_id' => $user->id]);
        $school = School::create([
            'name' => 'Colegio vacío',
            'status' => 'activo',
            'consultant_id' => $consultant->id,
        ]);
        $formatController = app(FormatoEntregaController::class);

        $summary = $formatController->summaryForSchool($school);
        $this->assertSame(0, $summary['studentCount']);
        $this->assertSame(0, $summary['teacherCount']);

        foreach (['alumnos' => 'No hay alumnos', 'docentes' => 'No hay docentes'] as $tipo => $message) {
            $response = $formatController->download($school, Request::create('/?tipo=' . $tipo, 'GET'));

            $this->assertSame(422, $response->getStatusCode());
            $this->assertStringContainsString($message, $response->getContent());
        }
    }

    public function test_it_exports_teachers_by_grade_with_student_compatible_columns(): void
    {
        $user = User::factory()->create();
        $consultant = Consultant::create(['user_id' => $user->id]);
        $school = School::create([
            'name' => 'Colegio de prueba',
            'status' => 'activo',
            'consultant_id' => $consultant->id,
        ]);

        Student::create([
            'school_id' => $school->id,
            'name' => 'Alex',
            'last_name' => 'Alumno Prueba',
            'mee_username' => 'alex',
            'mee_password' => 'clave',
            'level' => 'Primaria',
            'grade' => '1°A',
        ]);

        $teacherWithMissingCredentials = Teacher::create([
            'school_id' => $school->id,
            'name' => 'Maria',
            'last_name' => 'Cruz Lopez',
            'email' => 'maria@example.test',
            'mee_username' => 'maria.mee',
            'mee_password' => null,
            'grade' => '1°A',
            'subject' => 'ELT',
        ]);
        $teacherWithMissingCredentials->roles()->create(['role' => 'docente']);

        Teacher::create([
            'school_id' => $school->id,
            'name' => 'Luis',
            'last_name' => 'Garcia Perez',
            'email' => 'luis@example.test',
            'mee_username' => 'luis.mee',
            'mee_password' => 'pass123',
            'grade' => '2°',
            'subject' => 'ambos',
        ]);

        $formatController = app(FormatoEntregaController::class);
        $summary = $formatController->summaryForSchool($school);
        $this->assertSame(1, $summary['teacherReviewCount']);
        $this->assertSame(2, $summary['teacherClassCount']);

        $teacherResponse = $formatController->download($school, Request::create('/?tipo=docentes', 'GET'));
        $teacherPath = $teacherResponse->getFile()->getPathname();
        try {
            $this->assertSame('0', $teacherResponse->headers->get('X-Alumnos-Por-Revisar'));
            $this->assertSame('1', $teacherResponse->headers->get('X-Docentes-Por-Revisar'));
            $this->assertSame('2', $teacherResponse->headers->get('X-Grados-Docentes'));
            $this->assertStringContainsString('Cuentas de Docentes_Colegio de prueba_1.xlsx', urldecode($teacherResponse->headers->get('Content-Disposition')));

            $teacherWorkbook = IOFactory::load($teacherPath);
            $this->assertSame(['Todos los docentes', 'Docentes - 1°A', 'Docentes - 2'], $teacherWorkbook->getSheetNames());
            $teacherSummary = $teacherWorkbook->getSheet(0);
            $this->assertSame(['Nombre', 'Apellido paterno', 'Apellido materno', 'Usuario', 'Contraseña', 'Grado', 'Grupo', 'Género'], array_map(
                fn (string $column) => $teacherSummary->getCell($column . '6')->getValue(),
                range('A', 'H'),
            ));
            $this->assertSame('maria@example.test', $teacherSummary->getCell('D7')->getValue());
            $this->assertSame('1', $teacherSummary->getCell('F7')->getValue());
            $this->assertSame('A', $teacherSummary->getCell('G7')->getValue());
            $this->assertSame('Femenino', $teacherSummary->getCell('H7')->getValue());
            $this->assertNull($teacherSummary->getCell('E7')->getValue());
            $this->assertSame('2', $teacherSummary->getCell('F8')->getValue());
            $this->assertNull($teacherSummary->getCell('G8')->getValue());
        } finally {
            @unlink($teacherPath);
        }
    }
}