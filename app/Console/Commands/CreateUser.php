<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('erp:usuario {email} {--name= : Nombre que se muestra arriba a la derecha} {--password= : Si no se pasa, se pregunta (sin mostrarla)}')]
#[Description('Crea un usuario del ERP, o le cambia la contraseña si el email ya existe')]
class CreateUser extends Command
{
    /**
     * No hay registro público: los usuarios del ERP se crean desde la consola.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->option('password') ?? $this->secret('Contraseña (mínimo 8 caracteres)');

        $validator = Validator::make(['email' => $email, 'password' => $password], [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $isNew = ! $user->exists;

        $user->name = $this->option('name') ?? $user->name ?? strstr($email, '@', true);
        $user->password = $password;   // el cast 'hashed' la encripta
        $user->save();

        $this->info($isNew ? "Usuario {$email} creado." : "Contraseña de {$email} actualizada.");

        return self::SUCCESS;
    }
}
