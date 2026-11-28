<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** El registro público está cerrado: las cuentas las crea el admin en Equipo SI. */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_closed(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_nobody_can_register_by_post(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $this->assertFalse(User::where('email', 'test@example.com')->exists());
    }
}
