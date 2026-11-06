<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'siya:admin {email : adresse de connexion} {--name= : nom affiché}';

    protected $description = 'Crée (ou réinitialise le mot de passe de) un compte administrateur';

    public function handle(): int
    {
        $email = mb_strtolower((string) $this->argument('email'));

        if (Validator::make(['email' => $email], ['email' => 'required|email'])->fails()) {
            $this->error('Adresse email invalide.');

            return self::FAILURE;
        }

        // Le mot de passe est demandé de façon masquée : il n'apparaît ni à l'écran ni dans l'historique du terminal.
        $password = $this->secret('Mot de passe (12 caractères minimum)');
        if (strlen((string) $password) < 12) {
            $this->error('Le mot de passe doit contenir au moins 12 caractères.');

            return self::FAILURE;
        }
        if ($password !== $this->secret('Confirmez le mot de passe')) {
            $this->error('Les deux mots de passe sont différents.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        $user
            ? $user->update(['password' => $password, 'is_active' => true])
            : User::create(['name' => $this->option('name') ?: 'Administrateur', 'email' => $email, 'password' => $password]);

        $this->info($user ? 'Mot de passe réinitialisé.' : 'Compte créé.');
        $this->line('Connexion : '.url('/admin/connexion'));

        return self::SUCCESS;
    }
}
