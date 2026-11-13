<?php

namespace Tests\Feature;

use App\Models\Consultant;
use App\Models\School;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketProductosTest extends TestCase
{
    use RefreshDatabase;

    private function setupSchool(): array
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'admin']));
        $consultant = Consultant::create(['user_id' => $user->id]);
        $school = School::create(['name' => 'Colegio tickets', 'status' => 'activo']);
        $this->actingAs($user);

        return [$consultant, $school];
    }

    private function payload(Consultant $consultant, array $extra = []): array
    {
        return $extra + [
            'title'         => 'Alta de alumno',
            'description'   => 'Alta pendiente',
            'priority'      => 'medium',
            'consultant_id' => $consultant->id,
            'medium'        => 'whatsapp',
            'productos'     => ['MEE', 'Meta'],
        ];
    }

    public function test_ticket_stores_products_and_shows_them(): void
    {
        [$consultant, $school] = $this->setupSchool();

        $this->get(route('schools.tickets.create', $school))->assertOk()->assertSee('value="Meta"', false);

        $this->post(route('schools.tickets.store', $school), $this->payload($consultant))
            ->assertRedirect(route('schools.tickets.index', $school));

        $ticket = Ticket::firstOrFail();
        $this->assertSame(['MEE', 'Meta'], $ticket->productos);

        $this->get(route('schools.tickets.index', $school))->assertOk()->assertSee('🎯 Meta');
        $this->get(route('tickets.edit', $ticket))->assertOk()->assertSee('value="Meta" class="sesion-producto"', false);

        $this->put(route('tickets.update', $ticket), $this->payload($consultant, ['status' => 'closed', 'productos' => ['Lego']]))
            ->assertRedirect();
        $this->assertSame(['Lego'], $ticket->fresh()->productos);
    }

    public function test_ticket_requires_a_valid_product(): void
    {
        [$consultant, $school] = $this->setupSchool();

        $this->post(route('schools.tickets.store', $school), $this->payload($consultant, ['productos' => []]))
            ->assertSessionHasErrors(['productos']);
        $this->post(route('schools.tickets.store', $school), $this->payload($consultant, ['productos' => ['Inventado']]))
            ->assertSessionHasErrors(['productos.0']);

        $this->assertSame(0, Ticket::count());
    }

    public function test_old_tickets_without_products_still_render(): void
    {
        [$consultant, $school] = $this->setupSchool();
        $ticket = $school->tickets()->create(collect($this->payload($consultant))->except('productos')->all());

        $this->get(route('schools.tickets.index', $school))->assertOk();
        $this->get(route('tickets.edit', $ticket))->assertOk();
    }
}
