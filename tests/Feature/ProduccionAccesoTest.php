<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProduccionAccesoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $rol]));

        return $user;
    }

    public function test_read_only_roles_see_produccion_without_upload_or_delete(): void
    {
        foreach (['consultor_eca', 'consultor_elt', 'coordinador', 'representante_ventas'] as $rol) {
            $this->actingAs($this->usuario($rol));

            $this->get(route('produccion.index'))
                ->assertOk()
                ->assertSee(route('produccion.index'), false) // liga en el menú
                ->assertDontSee('Cargar Excel')
                ->assertDontSee('Borrar Dashboard');

            $this->delete(route('produccion.destroy'))->assertRedirect();
            $this->post(route('produccion.upload'))->assertRedirect();
        }

        $this->assertSame(0, ActivityLog::where('tipo', 'produccion')->count());
    }

    public function test_admin_can_still_manage_produccion(): void
    {
        $this->actingAs($this->usuario('admin'))
            ->get(route('produccion.index'))
            ->assertOk()
            ->assertSee('Cargar Excel');
    }
}
