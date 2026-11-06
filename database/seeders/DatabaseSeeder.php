<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductSeeder::class);

        // Sur Render Free, le Shell n'est pas disponible : le premier administrateur
        // peut être provisionné de façon privée via les variables d'environnement.
        $email = mb_strtolower(trim((string) env('SIYA_ADMIN_EMAIL')));
        $password = (string) env('SIYA_ADMIN_PASSWORD');

        if ($email === '' && $password === '') {
            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
            throw new InvalidArgumentException(
                'SIYA_ADMIN_EMAIL doit être valide et SIYA_ADMIN_PASSWORD doit contenir au moins 12 caractères.'
            );
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => trim((string) env('SIYA_ADMIN_NAME')) ?: 'Administrateur',
                'password' => $password,
                'is_active' => true,
            ],
        );
    }
}
