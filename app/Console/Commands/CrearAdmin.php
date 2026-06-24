<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CrearAdmin extends Command
{
    protected $signature = 'crm:crear-admin
                            {--name= : Nombre del administrador}
                            {--email= : Email del administrador}
                            {--password= : Contraseña (mín. 8 caracteres)}';

    protected $description = 'Crea el usuario administrador y los roles necesarios del CRM';

    public function handle(): int
    {
        $this->info('── SICentral CRM · Crear Administrador ──');

        // 1. Crear roles si no existen
        $roles = [
            'admin', 'consultor_digital', 'consultor_eca',
            'consultor_elt', 'representante_ventas',
        ];

        foreach ($roles as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        $this->line('✓ Roles verificados.');

        // 2. Pedir datos del admin
        $name = $this->option('name')
            ?? $this->ask('Nombre del administrador', 'Administrador');

        $email = $this->option('email')
            ?? $this->ask('Email del administrador');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('El email no es válido.');
            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("Ya existe un usuario con el email «{$email}».");
            return self::FAILURE;
        }

        $password = $this->option('password')
            ?? $this->secret('Contraseña (mín. 8 caracteres)');

        if (strlen($password) < 8) {
            $this->error('La contraseña debe tener al menos 8 caracteres.');
            return self::FAILURE;
        }

        // 3. Crear usuario y asignar rol admin
        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('admin');

        $this->newLine();
        $this->info("✓ Administrador creado correctamente.");
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Nombre', $user->name],
                ['Email',  $user->email],
                ['Rol',    'admin'],
            ]
        );

        return self::SUCCESS;
    }
}
