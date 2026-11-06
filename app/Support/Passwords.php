<?php

namespace App\Support;

class Passwords
{
    /**
     * Vrai hash bcrypt d'un mot de passe jeté. Comparé quand l'adresse est inconnue, il rend le temps de
     * réponse identique que le compte existe ou non.
     */
    public const DUMMY_HASH = '$2y$12$3/E5qfa6yA928JdRXrY4weUlJwRJLvW991Ee.3bKhuBpPh84XdtnC';
}
