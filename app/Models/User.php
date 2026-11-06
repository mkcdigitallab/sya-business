<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** Un compte de l'équipe Siya Business (administration). Les clients commandent sans compte. */
class User extends Authenticatable
{
    // Valeur par défaut aussi côté PHP : un compte tout juste créé est actif sans qu'on ait à relire la base.
    protected $attributes = ['is_active' => true];

    protected $fillable = ['name', 'email', 'password', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean'];
    }
}
