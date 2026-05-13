<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin      =               Role::create(['name' => 'admin']);
        $supervisor =               Role::create(['name' => 'supervisor']);
        $vendedor   =               Role::create(['name' => 'vendedor']);
        $consultor_digital   =      Role::create(['name' => 'consultor_digital']);
        $consultor_eca  =           Role::create(['name' => 'consultor_eca']);
        $consultor_elt  =           Role::create(['name' => 'consultor_elt']); 
        $representante_ventas =     Role::create(['name' => 'representante_ventas']);  

        // Crear usuario administrador
        $user = User::create([
            'name'     => 'Administrador',
            'email'    => 'admin@macmillansi.com',
            'password' => Hash::make('Admin1234!'),
        ]);

        $user->assignRole($admin);
    }
}
