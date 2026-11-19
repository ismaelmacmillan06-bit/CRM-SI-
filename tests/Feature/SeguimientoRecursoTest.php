<?php

namespace Tests\Feature;

use App\Http\Controllers\SeguimientoRecursoController;
use App\Models\SeguimientoRecurso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

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

    public function test_global_educa_has_active_formatos_and_manuales_tabs(): void
    {
        $tmpXlsx = tempnam(sys_get_temp_dir(), 'ge_test_') . '.xlsx';
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Alumnos');
        $sheet->fromArray([['Nombre', 'Grado'], ['Ana López', '1°A']], null, 'A1');
        (new Xlsx($spreadsheet))->save($tmpXlsx);

        $uploadedFormato = new UploadedFile($tmpXlsx, 'plantilla_alta_v2.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $uploadedManual = UploadedFile::fake()->create('manual_global_educa.pdf', 120, 'application/pdf');

        $this->actingAs($this->admin)
            ->post(route('seguimiento.recursos.store', 'global-educa'), [
                'titulo'    => 'Formato de Alta Global Educa',
                'categoria' => 'formatos',
                'audiencia' => ['todos'],
                'archivo'   => $uploadedFormato,
            ])
            ->assertRedirect(route('seguimiento.show', 'global-educa'));

        $this->actingAs($this->admin)
            ->post(route('seguimiento.recursos.store', 'global-educa'), [
                'titulo'    => 'Manual Operativo Global Educa',
                'categoria' => 'manuales',
                'audiencia' => ['todos'],
                'archivo'   => $uploadedManual,
            ])
            ->assertRedirect(route('seguimiento.show', 'global-educa'));

        $recurso = SeguimientoRecurso::where('categoria', 'formatos')->firstOrFail();

        $this->actingAs($this->eca)
            ->get(route('seguimiento.show', 'global-educa'))
            ->assertOk()
            ->assertSee('Tabla')
            ->assertSee('Formatos')
            ->assertSee('Manuales')
            ->assertSee('Manuales de Global Educa')
            ->assertSee('Formato de Alta Global Educa')
            ->assertSee('Manual Operativo Global Educa')
            ->assertDontSee('Próximamente');

        $this->actingAs($this->eca)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $recurso, 'mode' => 'sheet']))
            ->assertOk()
            ->assertJson([
                'ok'          => true,
                'sheetTitle'  => 'Alumnos',
                'totalRows'   => 2,
                'totalSheets' => 1,
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

    public function test_multisheet_xlsx_and_ods_preview_are_supported(): void
    {
        $tmpXlsx = tempnam(sys_get_temp_dir(), 'multi_') . '.xlsx';
        $spreadsheet = new Spreadsheet();
        $s1 = $spreadsheet->getActiveSheet();
        $s1->setTitle('Docentes');
        $s1->fromArray([['Nombre', 'Correo'], ['Lucía Pérez', 'lucia@colegio.mx']], null, 'A1');
        $s2 = $spreadsheet->createSheet();
        $s2->setTitle('Grupos');
        $s2->fromArray([['Grupo', 'Nivel'], ['3°B', 'Primaria']], null, 'A1');
        (new Xlsx($spreadsheet))->save($tmpXlsx);

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'frances'), [
            'titulo'    => 'Plantilla Multi Hoja',
            'categoria' => 'formatos',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpXlsx, 'multi.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertRedirect(route('seguimiento.show', 'frances'));

        $xlsxRecurso = SeguimientoRecurso::latest('id')->firstOrFail();

        $resp = $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $xlsxRecurso, 'mode' => 'sheet']))
            ->assertOk()
            ->assertJson([
                'ok'          => true,
                'sheetTitle'  => 'Docentes',
                'totalSheets' => 2,
            ]);

        $this->assertSame('Grupos', $resp->json('sheets.1.title'));
        $this->assertSame('Primaria', $resp->json('sheets.1.rows.1.1'));
        @unlink($tmpXlsx);

        $tmpOds = tempnam(sys_get_temp_dir(), 'ods_') . '.ods';
        $odsBook = new Spreadsheet();
        $odsSheet = $odsBook->getActiveSheet();
        $odsSheet->setTitle('Alumnos ODS');
        $odsSheet->fromArray([['Alumno', 'Grado'], ['Mateo Ruiz', '2°A']], null, 'A1');
        (new Ods($odsBook))->save($tmpOds);

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'global-educa'), [
            'titulo'    => 'Hoja Calc ODS',
            'categoria' => 'manuales',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpOds, 'alumnos.ods', 'application/vnd.oasis.opendocument.spreadsheet', null, true),
        ])->assertRedirect(route('seguimiento.show', 'global-educa'));

        $odsRecurso = SeguimientoRecurso::latest('id')->firstOrFail();
        $this->assertTrue($odsRecurso->formatoInfo()['previsualizable']);

        $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $odsRecurso, 'mode' => 'sheet']))
            ->assertOk()
            ->assertJson([
                'ok'         => true,
                'sheetTitle' => 'Alumnos ODS',
                'totalRows'  => 2,
            ]);

        @unlink($tmpOds);
    }

    public function test_word_docx_odt_and_legacy_doc_previews_return_structured_blocks(): void
    {
        // DOCX sintético
        $tmpDocx = tempnam(sys_get_temp_dir(), 'doc_') . '.docx';
        $zip = new ZipArchive();
        $zip->open($tmpDocx, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
              <w:body>
                <w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr><w:r><w:t>Guía de Arranque</w:t></w:r></w:p>
                <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Paso importante:</w:t></w:r><w:r><w:t xml:space="preserve"> revisar accesos en Francés.</w:t></w:r></w:p>
                <w:p><w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr><w:r><w:t>Verificar docentes</w:t></w:r></w:p>
                <w:tbl><w:tr><w:tc><w:p><w:r><w:t>Rol</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>Acción</w:t></w:r></w:p></w:tc></w:tr></w:tbl>
              </w:body>
            </w:document>');
        $zip->close();

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'global-educa'), [
            'titulo'    => 'Manual Word DOCX',
            'categoria' => 'manuales',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpDocx, 'manual.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true),
        ]);

        $docxRecurso = SeguimientoRecurso::latest('id')->firstOrFail();
        $this->assertTrue($docxRecurso->formatoInfo()['previsualizable']);

        $docxResp = $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $docxRecurso, 'mode' => 'doc']))
            ->assertOk()
            ->assertJson(['ok' => true, 'format' => 'DOCX']);

        $this->assertSame('heading', $docxResp->json('blocks.0.type'));
        $this->assertSame('Guía de Arranque', $docxResp->json('blocks.0.text'));
        $this->assertSame('list_item', $docxResp->json('blocks.2.type'));
        $this->assertSame('table', $docxResp->json('blocks.3.type'));
        @unlink($tmpDocx);

        // ODT sintético
        $tmpOdt = tempnam(sys_get_temp_dir(), 'odt_') . '.odt';
        $zipOdt = new ZipArchive();
        $zipOdt->open($tmpOdt, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zipOdt->addFromString('content.xml', '<?xml version="1.0" encoding="UTF-8"?>
            <office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
                xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
                xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0">
              <office:body><office:text>
                <text:h text:outline-level="1">Encabezado ODT</text:h>
                <text:p>Contenido en español con acentos: áéíóú.</text:p>
              </office:text></office:body>
            </office:document-content>');
        $zipOdt->close();

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'red-roo-reads'), [
            'titulo'    => 'Guía ODT',
            'categoria' => 'manuales',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpOdt, 'guia.odt', 'application/vnd.oasis.opendocument.text', null, true),
        ]);

        $odtRecurso = SeguimientoRecurso::latest('id')->firstOrFail();
        $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $odtRecurso, 'mode' => 'doc']))
            ->assertOk()
            ->assertJson(['ok' => true, 'format' => 'ODT'])
            ->assertJsonPath('blocks.0.text', 'Encabezado ODT')
            ->assertJsonPath('blocks.1.text', 'Contenido en español con acentos: áéíóú.');
        @unlink($tmpOdt);

        // Legacy DOC (RTF)
        $tmpRtfDoc = tempnam(sys_get_temp_dir(), 'rtf_') . '.doc';
        file_put_contents($tmpRtfDoc, "{\\rtf1\\ansi{\\fonttbl\\f0 Arial;}Hola desde Word heredado\\par Segundo p\\'e1rrafo}");

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'equipamiento'), [
            'titulo'    => 'Formato DOC RTF',
            'categoria' => 'formatos',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpRtfDoc, 'formato_rtf.doc', 'application/msword', null, true),
        ]);

        $rtfRecurso = SeguimientoRecurso::latest('id')->firstOrFail();
        $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $rtfRecurso, 'mode' => 'doc']))
            ->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonPath('blocks.0.text', 'Hola desde Word heredado')
            ->assertJsonPath('blocks.1.text', 'Segundo párrafo');
        @unlink($tmpRtfDoc);

        // Legacy DOC (UTF-16LE binario)
        $tmpBinDoc = tempnam(sys_get_temp_dir(), 'bin_') . '.doc';
        $u16Payload = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" . str_repeat("\x00", 32)
            . mb_convert_encoding("Documento binario heredado\rContenido legible en Francés", 'UTF-16LE', 'UTF-8')
            . str_repeat("\x00", 16);
        file_put_contents($tmpBinDoc, $u16Payload);

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'frances'), [
            'titulo'    => 'Formato DOC Binario',
            'categoria' => 'formatos',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpBinDoc, 'formato_bin.doc', 'application/msword', null, true),
        ]);

        $binRecurso = SeguimientoRecurso::latest('id')->firstOrFail();
        $binResp = $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $binRecurso, 'mode' => 'doc']))
            ->assertOk()
            ->assertJson(['ok' => true, 'format' => 'DOC']);

        $this->assertNotEmpty($binResp->json('notice'));
        $this->assertSame('Documento binario heredado', $binResp->json('blocks.0.text'));
        @unlink($tmpBinDoc);
    }

    public function test_presentation_pptx_and_odp_previews_return_slides(): void
    {
        $tmpPptx = tempnam(sys_get_temp_dir(), 'ppt_') . '.pptx';
        $zip = new ZipArchive();
        $zip->open($tmpPptx, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $slideXml = fn (string $title, string $bullet) => '<?xml version="1.0" encoding="UTF-8"?>
            <p:sld xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"
                   xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
              <p:cSld><p:spTree>
                <p:sp><p:nvSpPr><p:cNvPr id="2" name="Title"/><p:cNvSpPr/><p:nvPr><p:ph type="title"/></p:nvPr></p:nvSpPr>
                  <p:txBody><a:bodyPr/><a:lstStyle/><a:p><a:r><a:t>' . $title . '</a:t></a:r></a:p></p:txBody>
                </p:sp>
                <p:sp><p:nvSpPr><p:cNvPr id="3" name="Body"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>
                  <p:txBody><a:bodyPr/><a:lstStyle/><a:p><a:r><a:t>' . $bullet . '</a:t></a:r></a:p></p:txBody>
                </p:sp>
              </p:spTree></p:cSld>
            </p:sld>';
        $zip->addFromString('ppt/slides/slide1.xml', $slideXml('Bienvenida Global Educa', 'Configuración inicial'));
        $zip->addFromString('ppt/slides/slide2.xml', $slideXml('Cierre', 'Soporte y seguimiento'));
        $zip->close();

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'global-educa'), [
            'titulo'    => 'Capacitación PPTX',
            'categoria' => 'manuales',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpPptx, 'capacitacion.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', null, true),
        ]);

        $pptxRecurso = SeguimientoRecurso::latest('id')->firstOrFail();
        $this->assertTrue($pptxRecurso->formatoInfo()['previsualizable']);

        $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $pptxRecurso, 'mode' => 'slides']))
            ->assertOk()
            ->assertJson(['ok' => true, 'format' => 'PPTX', 'totalSlides' => 2])
            ->assertJsonPath('slides.0.title', 'Bienvenida Global Educa')
            ->assertJsonPath('slides.0.items.0.text', 'Configuración inicial')
            ->assertJsonPath('slides.1.title', 'Cierre');
        @unlink($tmpPptx);

        $tmpOdp = tempnam(sys_get_temp_dir(), 'odp_') . '.odp';
        $zipOdp = new ZipArchive();
        $zipOdp->open($tmpOdp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zipOdp->addFromString('content.xml', '<?xml version="1.0" encoding="UTF-8"?>
            <office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
                xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
                xmlns:presentation="urn:oasis:names:tc:opendocument:xmlns:presentation:1.0"
                xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">
              <office:body><office:presentation>
                <draw:page draw:name="page1">
                  <draw:frame presentation:class="title"><draw:text-box><text:p>Portada ODP</text:p></draw:text-box></draw:frame>
                  <draw:frame presentation:class="outline"><draw:text-box><text:p>Punto clave 1</text:p></draw:text-box></draw:frame>
                </draw:page>
              </office:presentation></office:body>
            </office:document-content>');
        $zipOdp->close();

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'meta'), [
            'titulo'    => 'Presentación ODP',
            'categoria' => 'manuales',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpOdp, 'slides.odp', 'application/vnd.oasis.opendocument.presentation', null, true),
        ]);

        $odpRecurso = SeguimientoRecurso::latest('id')->firstOrFail();
        $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $odpRecurso, 'mode' => 'slides']))
            ->assertOk()
            ->assertJson(['ok' => true, 'format' => 'ODP', 'totalSlides' => 1])
            ->assertJsonPath('slides.0.title', 'Portada ODP')
            ->assertJsonPath('slides.0.items.0.text', 'Punto clave 1');
        @unlink($tmpOdp);
    }

    public function test_archive_zip_and_rar_previews(): void
    {
        $tmpZip = tempnam(sys_get_temp_dir(), 'pack_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('README.txt', 'Instrucciones del paquete');
        $zip->addFromString('plantillas/ejemplo.csv', "a,b\n1,2\n");
        $zip->close();

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'frances'), [
            'titulo'    => 'Paquete ZIP',
            'categoria' => 'formatos',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpZip, 'paquete.zip', 'application/zip', null, true),
        ]);

        $zipRecurso = SeguimientoRecurso::latest('id')->firstOrFail();
        $this->assertTrue($zipRecurso->formatoInfo()['previsualizable']);

        $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $zipRecurso, 'mode' => 'archive']))
            ->assertOk()
            ->assertJson(['ok' => true, 'format' => 'ZIP', 'totalEntries' => 2, 'totalFiles' => 2])
            ->assertJsonPath('entries.0.name', 'README.txt')
            ->assertJsonPath('entries.1.name', 'plantillas/ejemplo.csv');
        @unlink($tmpZip);

        $rarFile = UploadedFile::fake()->create('archivo.rar', 10);
        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'equipamiento'), [
            'titulo'    => 'Paquete RAR',
            'categoria' => 'manuales',
            'audiencia' => ['todos'],
            'archivo'   => $rarFile,
        ]);

        $rarRecurso = SeguimientoRecurso::latest('id')->firstOrFail();
        $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $rarRecurso, 'mode' => 'archive']))
            ->assertOk()
            ->assertJson(['ok' => true, 'format' => 'RAR', 'unsupportedListing' => true]);
    }

    public function test_seguimiento_menus_always_default_to_tabla_without_localstorage_persistence(): void
    {
        foreach (['meta', 'global-educa', 'frances', 'red-roo-reads', 'equipamiento'] as $plataforma) {
            $this->actingAs($this->admin)
                ->get(route('seguimiento.show', $plataforma))
                ->assertOk()
                ->assertSee($plataforma === 'meta' ? 'id="mt-vista-tabla"' : 'id="ge-vista-tabla"', false)
                ->assertDontSee('vistaGuardada', false)
                ->assertDontSee('sic-ge-vista', false)
                ->assertDontSee('sic-meta-vista', false)
                ->assertDontSee('sic-seg-vista-', false);
        }
    }

    public function test_only_admin_can_upload_edit_update_audience_or_delete_resources_across_all_seguimiento_platforms(): void
    {
        // Recurso semilla para auditar PUT (update / audiencia) y DELETE en todos los roles no-admin
        $seedFile = UploadedFile::fake()->create('seed.pdf', 50, 'application/pdf');
        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'global-educa'), [
            'titulo'    => 'Recurso Base Auditoría',
            'categoria' => 'formatos',
            'audiencia' => ['todos'],
            'archivo'   => $seedFile,
        ])->assertRedirect(route('seguimiento.show', 'global-educa'));

        $recurso = SeguimientoRecurso::firstOrFail();

        $rolesConLectura = ['consultor_digital', 'consultor_eca', 'consultor_elt', 'coordinador', 'representante_ventas'];
        $todosNoAdmin = array_merge($rolesConLectura, ['supervisor', 'vendedor']);

        foreach ($todosNoAdmin as $rol) {
            $user = $this->crearUsuarioConRol($rol);

            foreach (SeguimientoRecursoController::CATEGORIAS_POR_PLATAFORMA as $plataforma => $categorias) {
                foreach ($categorias as $categoria) {
                    $file = UploadedFile::fake()->create('intento.pdf', 20, 'application/pdf');
                    $res = $this->actingAs($user)->post(route('seguimiento.recursos.store', $plataforma), [
                        'titulo'    => "Intento {$rol}",
                        'categoria' => $categoria,
                        'audiencia' => ['todos'],
                        'archivo'   => $file,
                    ]);
                    $this->assertTrue(in_array($res->getStatusCode(), [403, 302], true), "POST {$plataforma}/{$categoria} debería bloquear al rol {$rol}");
                }

                if (in_array($rol, $rolesConLectura, true)) {
                    $this->actingAs($user)
                        ->get(route('seguimiento.show', $plataforma))
                        ->assertOk()
                        ->assertDontSee('id="sr-upload-backdrop"', false)
                        ->assertDontSee('id="sr-edit-backdrop"', false)
                        ->assertDontSee('Subir manual', false)
                        ->assertDontSee('Subir formato', false)
                        ->assertDontSee('✏️ Editar', false);
                }
            }

            $upd = $this->actingAs($user)->put(route('seguimiento.recursos.update', $recurso), [
                'titulo'    => "Editado por {$rol}",
                'audiencia' => ['todos'],
            ]);
            $this->assertTrue(in_array($upd->getStatusCode(), [403, 302], true));

            $aud = $this->actingAs($user)->put(route('seguimiento.recursos.audiencia.update', $recurso), [
                'audiencia' => ['consultor_digital'],
            ]);
            $this->assertTrue(in_array($aud->getStatusCode(), [403, 302], true));

            $del = $this->actingAs($user)->delete(route('seguimiento.recursos.destroy', $recurso));
            $this->assertTrue(in_array($del->getStatusCode(), [403, 302], true));
        }

        $recurso->refresh();
        $this->assertSame('Recurso Base Auditoría', $recurso->titulo);
        $this->assertSame(['todos'], $recurso->audienciaRoles());
        $this->assertSame(1, SeguimientoRecurso::count());

        // Admin sí puede subir en todas las plataformas y categorías habilitadas
        $esperados = 1;
        foreach (SeguimientoRecursoController::CATEGORIAS_POR_PLATAFORMA as $plataforma => $categorias) {
            $this->actingAs($this->admin)
                ->get(route('seguimiento.show', $plataforma))
                ->assertOk()
                ->assertSee('id="sr-upload-backdrop"', false);

            foreach ($categorias as $categoria) {
                $file = UploadedFile::fake()->create("{$plataforma}_{$categoria}.pdf", 20, 'application/pdf');
                $this->actingAs($this->admin)
                    ->post(route('seguimiento.recursos.store', $plataforma), [
                        'titulo'    => "Admin {$plataforma} {$categoria}",
                        'categoria' => $categoria,
                        'audiencia' => ['todos'],
                        'archivo'   => $file,
                    ])
                    ->assertRedirect(route('seguimiento.show', $plataforma))
                    ->assertSessionHas('tab', $categoria);
                $esperados++;
            }
        }

        $this->assertSame($esperados, SeguimientoRecurso::count());
    }

    public function test_preview_hardening_cp1252_csv_corrupt_xlsx_non_utf8_zip_and_xml_entity_rejection(): void
    {
        // 1. CSV en Windows-1252 (Excel español): no debe tronar JSON y debe salir en UTF-8
        $tmpCsv = tempnam(sys_get_temp_dir(), 'win_') . '.csv';
        file_put_contents($tmpCsv, mb_convert_encoding("Alumno,Grado\r\nAna López,1°A\r\n", 'Windows-1252', 'UTF-8'));

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'global-educa'), [
            'titulo'    => 'CSV Español CP1252',
            'categoria' => 'formatos',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpCsv, 'espanol.csv', 'text/csv', null, true),
        ]);
        $csvRecurso = SeguimientoRecurso::latest('id')->firstOrFail();

        $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $csvRecurso, 'mode' => 'sheet']))
            ->assertOk()
            ->assertJson(['ok' => true, 'totalRows' => 2])
            ->assertJsonPath('rows.1.0', 'Ana López');
        @unlink($tmpCsv);

        // 2. XLSX corrupto con texto adentro: NO debe caer al Csv reader ni reportar ok=true
        $tmpBadXlsx = tempnam(sys_get_temp_dir(), 'bad_') . '.xlsx';
        file_put_contents($tmpBadXlsx, "esto no es un xlsx real, tiene comas");

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'global-educa'), [
            'titulo'    => 'XLSX Corrupto',
            'categoria' => 'formatos',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpBadXlsx, 'corrupto.xlsx', 'application/octet-stream', null, true),
        ]);
        $badXlsxRecurso = SeguimientoRecurso::latest('id')->firstOrFail();

        $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $badXlsxRecurso, 'mode' => 'sheet']))
            ->assertOk()
            ->assertJson(['ok' => false]);
        @unlink($tmpBadXlsx);

        // 3. ZIP con nombre de archivo no UTF-8 (Latin-1 / CP850) y ZIP vacío
        $tmpLatinZip = tempnam(sys_get_temp_dir(), 'z850_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($tmpLatinZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $rawName = mb_convert_encoding('Guía_Español.txt', 'CP850', 'UTF-8');
        $zip->addFromString($rawName, 'Contenido');
        $zip->close();

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'frances'), [
            'titulo'    => 'ZIP Acentos Windows',
            'categoria' => 'manuales',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpLatinZip, 'windows_zip.zip', 'application/zip', null, true),
        ]);
        $latinZipRecurso = SeguimientoRecurso::latest('id')->firstOrFail();

        $zipResp = $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $latinZipRecurso, 'mode' => 'archive']))
            ->assertOk()
            ->assertJson(['ok' => true, 'format' => 'ZIP', 'totalEntries' => 1]);
        $this->assertTrue(mb_check_encoding((string) $zipResp->json('entries.0.name'), 'UTF-8'));
        @unlink($tmpLatinZip);

        $tmpEmptyZip = tempnam(sys_get_temp_dir(), 'zemp_') . '.zip';
        file_put_contents($tmpEmptyZip, "PK\x05\x06" . str_repeat("\x00", 18));
        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'meta'), [
            'titulo'    => 'ZIP Vacío',
            'categoria' => 'manuales',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpEmptyZip, 'vacio.zip', 'application/zip', null, true),
        ]);
        $emptyZipRecurso = SeguimientoRecurso::latest('id')->firstOrFail();

        $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $emptyZipRecurso, 'mode' => 'archive']))
            ->assertOk()
            ->assertJson(['ok' => true, 'format' => 'ZIP', 'totalEntries' => 0, 'entries' => []]);
        @unlink($tmpEmptyZip);

        // 4. DOCX con <!ENTITY (defensa contra XML Entity Expansion / XXE)
        $tmpEntityDocx = tempnam(sys_get_temp_dir(), 'xxe_') . '.docx';
        $zipDoc = new ZipArchive();
        $zipDoc->open($tmpEntityDocx, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zipDoc->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?>
            <!DOCTYPE doc [<!ENTITY lol "lol">]>
            <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>&lol;</w:t></w:r></w:p></w:body></w:document>');
        $zipDoc->close();

        $this->actingAs($this->admin)->post(route('seguimiento.recursos.store', 'global-educa'), [
            'titulo'    => 'DOCX Entidad',
            'categoria' => 'manuales',
            'audiencia' => ['todos'],
            'archivo'   => new UploadedFile($tmpEntityDocx, 'entity.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true),
        ]);
        $entityDocxRecurso = SeguimientoRecurso::latest('id')->firstOrFail();

        $this->actingAs($this->digital)
            ->get(route('seguimiento.recursos.preview', ['recurso' => $entityDocxRecurso, 'mode' => 'doc']))
            ->assertOk()
            ->assertJson(['ok' => false]);
        @unlink($tmpEntityDocx);
    }
}
