<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ScriptReporteAccesos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Script de consola del Reporte de Accesos: solo el admin lo edita, elimina o restaura (sin tocar el repo). */
class ScriptReporteAccesosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        foreach (['admin', 'consultor_digital', 'consul_ged'] as $rol) {
            Role::firstOrCreate(['name' => $rol]);
        }
    }

    private function usuario(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole($rol);

        return $user;
    }

    public function test_tool_shows_original_script_by_default(): void
    {
        $original = app(ScriptReporteAccesos::class)->original();
        $this->assertStringContainsString('listUrlTemplate', $original);

        $this->actingAs($this->usuario('consultor_digital'))
            ->get(route('herramientas.reporte-accesos'))->assertOk()
            ->assertSee('listUrlTemplate')->assertDontSee('Editar script (admin)');
    }

    public function test_admin_edits_deletes_and_restores_the_script(): void
    {
        $admin = $this->usuario('admin');
        $digital = $this->usuario('consultor_digital');
        $nuevo = "(async () => {\n  console.log('v2 <b>&</b>');\n})();";

        $this->actingAs($admin)->get(route('herramientas.reporte-accesos.script'))->assertOk()->assertSee('Script original');
        $this->get(route('herramientas.reporte-accesos'))->assertSee('Editar script (admin)')->assertSee('<!--sic-admin-->', false);

        // Editar: todos ven el script nuevo (escapado) en la herramienta
        $this->put(route('herramientas.reporte-accesos.script.update'), ['script' => $nuevo])->assertRedirect();
        $html = $this->actingAs($digital)->get(route('herramientas.reporte-accesos'))->assertOk()->getContent();
        $this->assertStringContainsString('console.log(&#039;v2 &lt;b&gt;&amp;&lt;/b&gt;&#039;)', $html);
        $this->assertStringNotContainsString('listUrlTemplate', $html);
        $this->assertStringNotContainsString('<b>&</b>', $html);

        // Eliminar: aviso y botón Copiar desactivado
        $this->actingAs($admin)->delete(route('herramientas.reporte-accesos.script.destroy'))->assertRedirect();
        $this->actingAs($digital)->get(route('herramientas.reporte-accesos'))
            ->assertSee(ScriptReporteAccesos::AVISO_SIN_SCRIPT)->assertSee('Sin script disponible')->assertDontSee('v2 &lt;b', false);

        // Restaurar: vuelve el original
        $this->actingAs($admin)->post(route('herramientas.reporte-accesos.script.restore'))->assertRedirect();
        $this->actingAs($digital)->get(route('herramientas.reporte-accesos'))->assertSee('listUrlTemplate');
        $this->assertDatabaseHas('activity_logs', ['descripcion' => 'Script del Reporte de Accesos actualizado']);
    }

    public function test_empty_script_is_rejected(): void
    {
        $this->actingAs($this->usuario('admin'))
            ->put(route('herramientas.reporte-accesos.script.update'), ['script' => '  '])
            ->assertSessionHasErrors('script');
        $this->assertSame('original', app(ScriptReporteAccesos::class)->actual()['estado']);
    }

    public function test_only_admin_can_touch_the_script(): void
    {
        $this->actingAs($this->usuario('consultor_digital'));
        $this->get(route('herramientas.reporte-accesos.script'))->assertForbidden();
        $this->put(route('herramientas.reporte-accesos.script.update'), ['script' => 'x'])->assertForbidden();
        $this->delete(route('herramientas.reporte-accesos.script.destroy'))->assertForbidden();
        $this->post(route('herramientas.reporte-accesos.script.restore'))->assertForbidden();
        $this->get(route('herramientas.index'))->assertDontSee('Editar script de consola');

        $this->actingAs($this->usuario('consul_ged'));
        $this->get(route('herramientas.reporte-accesos.script'))->assertForbidden();
        $this->put(route('herramientas.reporte-accesos.script.update'), ['script' => 'x'])->assertSessionHas('error_acceso');

        $this->assertSame('original', app(ScriptReporteAccesos::class)->actual()['estado']);
    }
}
