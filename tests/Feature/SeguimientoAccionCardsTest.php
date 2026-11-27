<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Cards por acción de arranque en Seguimiento: cuántos colegios van en cada acción, con detalle. */
class SeguimientoAccionCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cards_follow_the_editable_catalog(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $school = School::create(['name' => 'Colegio Kickoff', 'status' => 'activo']);
        $school->services()->attach(SchoolServiceType::where('plataforma', 'global-educa')->value('id'));

        $this->actingAs($admin);
        $html = $this->get(route('seguimiento.show', 'global-educa'))->assertOk()->getContent();
        $this->assertStringContainsString('ACCIÓN 1', $html);
        // El colegio sin avance está en la primera acción y aparece en su detalle
        $this->assertMatchesRegularExpression('/<template id="acc-detalle-[^"]+">.*Colegio Kickoff/s', $html);

        // Una acción nueva del catálogo genera su card
        $this->post(route('seguimiento.acciones.store', 'global-educa'), ['etiqueta' => 'Acción agregada de prueba']);
        $this->get(route('seguimiento.show', 'global-educa'))->assertSee('Acción agregada de prueba');
    }
}
