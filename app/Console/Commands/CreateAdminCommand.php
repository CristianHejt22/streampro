<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cstreame:admin 
                            {--email= : Correo electrónico del administrador} 
                            {--password= : Contraseña de acceso} 
                            {--name= : Nombre del administrador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea o actualiza el usuario Administrador General de CStreame para Producción';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('====================================================');
        $this->info('  CSTREAME - Crear/Actualizar Administrador General');
        $this->info('====================================================');

        $email = $this->option('email') ?: $this->ask('Ingresa el correo electrónico del Administrador');
        while (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Formato de correo inválido.');
            $email = $this->ask('Ingresa un correo electrónico válido');
        }

        $password = $this->option('password') ?: $this->secret('Ingresa la contraseña para el Administrador');
        while (strlen($password) < 6) {
            $this->error('La contraseña debe tener al menos 6 caracteres.');
            $password = $this->secret('Ingresa una contraseña segura (mínimo 6 caracteres)');
        }

        $name = $this->option('name') ?: $this->ask('Nombre del Administrador', 'Administrador General');

        $user = User::where('email', $email)->first();

        if ($user) {
            $this->warn("El usuario con email '{$email}' ya existe. Se actualizarán sus permisos y contraseña.");
            $user->name = $name;
            $user->password = Hash::make($password);
            $user->plain_password = $password;
            $user->role = 'admin';
            $user->panel_type = 'master';
            $user->status = 'active';
            $user->save();
            $action = 'actualizado';
        } else {
            $antiPhishing = 'CST-SEC-' . strtoupper(Str::random(6));
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'plain_password' => $password,
                'role' => 'admin',
                'panel_type' => 'master',
                'status' => 'active',
                'balance' => 0.00,
                'reseller_plan' => 'mayorista',
                'referral_code' => 'ADMIN-' . strtoupper(Str::random(4)),
                'anti_phishing_code' => $antiPhishing,
                'brand_name' => 'CStreame Master',
                'brand_primary_color' => '#E50914',
                'brand_accent_color' => '#06B6D4',
            ]);
            $action = 'creado';
        }

        $this->newLine();
        $this->info("[✓] Administrador General {$action} exitosamente!");
        $this->table(
            ['ID', 'Nombre', 'Email', 'Rol', 'Tipo de Panel', 'Estado'],
            [[
                $user->id,
                $user->name,
                $user->email,
                $user->role,
                $user->panel_type,
                $user->status,
            ]]
        );
        $this->info('====================================================');
        $this->comment('Ya puedes iniciar sesión en /login con estas credenciales.');

        return 0;
    }
}
