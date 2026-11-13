<?php

namespace Tests\Feature;

use App\Models\SeguimientoRecurso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeguimientoRecursoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $digital;
    private User $eca;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->admin = $this->crearUsuarioConRol('admin');
        $this->digital = $this->crearUsuarioConRol('consultor_digital');
        $this->eca = $this->crearUsuarioConRol('consultor_eca');
    }

    private function crearUsuarioConRol(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $rol]));
        return $user;
    }

    public function test_admin_can_upload_manual_in_meta_with_role_permissions(): void
    {
        $file = UploadedFile::fake()->create('manual_tecnico_v3_final.pdf', 120, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'meta'), [
            'titulo'      => 'Guía de Arranque Meta',
            'descripcion' => 'Manual paso a paso para arranque.',
            'categoria'   => 'manuales',
            'audiencia'   => ['consultor_digital', 'coordinador'],
            'archivo'     => $file,
        ]);

        $response->assertRedirect(route('seguimiento.show', 'meta'));

        $recurso = SeguimientoRecurso::first();
        $this->assertNotNull($recurso);
        $this->assertSame('meta', $recurso->plataforma);
        $this->assertSame('manuales', $recurso->categoria);
        $this->assertSame('Guía de Arranque Meta', $recurso->titulo);
        $this->assertSame('pdf', $recurso->extension);
        $this->assertEqualsCanonicalizing(['consultor_digital', 'coordinador'], $recurso->audienciaRoles());
        Storage::disk('local')->assertExists($recurso->archivo);
    }

    public function test_non_admin_cannot_upload_edit_or_delete_resources(): void
    {
        $file = UploadedFile::fake()->create('formato.docx', 50);

        $this->actingAs($this->digital)
            ->post(route('seguimiento.recursos.store', 'meta'), [
                'titulo'    => 'Intento no autorizado',
                'categoria' => 'manuales',
                'audiencia' => ['todos'],
                'archivo'   => $file,
            ])
            ->assertForbidden();

        $this->actingAs($this->eca)
            ->post(route('seguimiento.recursos.store', 'global-educa'), [
                'titulo'    => 'Intento ECA',
                'categoria' => 'formatos',
                'audiencia' => ['todos'],
                'archivo'   => $file,
            ])
            ->assertRedirect(); // VerificarAccesoRol bloquea POST de ECA en seguimiento.*

        $this->assertSame(0, SeguimientoRecurso::count());
    }

    public function test_visibility_and_download_respect_selected_roles(): void
    {
        $file = UploadedFile::fake()->create('guia.pdf', 80, 'application/pdf');

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'meta'), [
            'titulo'    => 'Solo Consultor Digital',
            'categoria' => 'manuales',
            'audiencia' => ['consultor_digital'],
            'archivo'   => $file,
        ]);

        $recurso = SeguimientoRecurso::firstOrFail();

        // Consultor digital sí lo ve y descarga
        $this->actingAs($this->digital)
            ->get(route('seguimiento.show', 'meta'))
            ->assertOk()
            ->assertSee('Solo Consultor Digital')
            ->assertSee('Manuales');

        $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', $recurso))
            ->assertOk();

        $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.download', $recurso))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=solo_consultor_digital.pdf');

        // Consultor ECA NO lo ve ni puede descargarlo/previsualizarlo
        $this->actingAs($this->eca)
            ->get(route('seguimiento.show', 'meta'))
            ->assertOk()
            ->assertDontSee('Solo Consultor Digital');

        $this->actingAs($this->eca)
            ->get(route('seguimiento.recursos.preview', $recurso))
            ->assertForbidden();

        $this->actingAs($this->eca)
            ->get(route('seguimiento.recursos.download', $recurso))
            ->assertForbidden();
    }

    public function test_global_educa_has_tabs_formatos_and_manuales_proximamente(): void
    {
        $tmpXlsx = tempnam(sys_get_temp_dir(), 'ge_test_') . '.xlsx';
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Alumnos');
        $sheet->fromArray([['Nombre', 'Grado'], ['Ana López', '1°A']], null, 'A1');
        (new Xlsx($spreadsheet))->save($tmpXlsx);

        $uploaded = new UploadedFile($tmpXlsx, 'plantilla_alta_v2.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($this->admin)
            ->post(route('seguimiento.recursos.store', 'global-educa'), [
                'titulo'    => 'Formato de Alta Global Educa',
                'categoria' => 'formatos',
                'audiencia' => ['todos'],
                'archivo'   => $uploaded,
            ])
            ->assertRedirect(route('seguimiento.show', 'global-educa'));

        $recurso = SeguimientoRecurso::firstOrFail();

        $this->actingAs($this->eca)
            ->get(route('seguimiento.show', 'global-educa'))
            ->assertOk()
            ->assertSee('Tabla')
            ->assertSee('Formatos')
            ->assertSee('Manuales')
            ->assertSee('Próximamente')
            ->assertSee('Formato de Alta Global Educa');

        $this->actingAs($this->eca)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $recurso, 'mode' => 'sheet']))
            ->assertOk()
            ->assertJson([
                'ok'         => true,
                'sheetTitle' => 'Alumnos',
                'totalRows'  => 2,
            ]);

        @unlink($tmpXlsx);
    }

    public function test_admin_can_edit_all_fields_and_delete_resource(): void
    {
        $file = UploadedFile::fake()->create('doc.docx', 40);

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'global-educa'), [
            'titulo'      => 'Formato Word',
            'descripcion' => 'Versión inicial',
            'categoria'   => 'formatos',
            'audiencia'   => ['consultor_digital'],
            'archivo'     => $file,
        ]);

        $recurso = SeguimientoRecurso::firstOrFail();
        $rutaAnterior = $recurso->archivo;
        $nuevoArchivo = UploadedFile::fake()->create('formato_v2.pdf', 90, 'application/pdf');

        // Consultor digital no puede editar ni ve botones de edición/eliminación
        $this->actingAs($this->digital)
            ->get(route('seguimiento.show', 'global-educa'))
            ->assertOk()
            ->assertDontSee('✏️ Editar');

        $this->actingAs($this->digital)
            ->put(route('seguimiento.recursos.update', $recurso), [
                'titulo'    => 'Hack',
                'audiencia' => ['todos'],
            ])
            ->assertForbidden();

        // Admin edita título, descripción, roles y reemplaza archivo
        $this->actingAs($this->admin)
            ->put(route('seguimiento.recursos.update', $recurso), [
                'titulo'      => 'Formato Oficial V2',
                'descripcion' => 'Actualizado para 2026',
                'audiencia'   => ['consultor_eca', 'representante_ventas'],
                'archivo'     => $nuevoArchivo,
            ])
            ->assertRedirect(route('seguimiento.show', 'global-educa'));

        $recurso->refresh();
        $this->assertSame('Formato Oficial V2', $recurso->titulo);
        $this->assertSame('Actualizado para 2026', $recurso->descripcion);
        $this->assertSame('pdf', $recurso->extension);
        $this->assertEqualsCanonicalizing(
            ['consultor_eca', 'representante_ventas'],
            $recurso->audienciaRoles()
        );
        Storage::disk('local')->assertMissing($rutaAnterior);
        Storage::disk('local')->assertExists($recurso->archivo);

        $rutaActual = $recurso->archivo;

        $this->actingAs($this->admin)
            ->delete(route('seguimiento.recursos.destroy', $recurso))
            ->assertRedirect(route('seguimiento.show', 'global-educa'));

        $this->assertDatabaseMissing('seguimiento_recursos', ['id' => $recurso->id]);
        Storage::disk('local')->assertMissing($rutaActual);
    }
}
