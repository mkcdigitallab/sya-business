<?php

/*
|--------------------------------------------------------------------------
| Réglages métier de Siya Business
|--------------------------------------------------------------------------
| Tout ce qui peut changer sans toucher au code vit ici (et dans le fichier .env).
| Un réglage laissé vide n'est tout simplement pas affiché sur le site :
| rien n'est inventé (adresse, réseaux sociaux, numéro de paiement…).
*/

return [
    // Contact public (appel téléphonique uniquement : aucune commande ne passe par WhatsApp).
    'phone' => env('SIYA_PHONE_NUMBER', '221781284366'),
    'email' => env('SIYA_EMAIL'),
    'address' => env('SIYA_ADDRESS'),
    'hours' => env('SIYA_HOURS'),

    // Numéro Wave / Orange Money pour régler les acomptes (vide = « nous vous communiquerons les modalités »).
    'payment_number' => env('SIYA_PAYMENT_NUMBER'),

    // Réseaux sociaux : seuls ceux renseignés apparaissent dans le pied de page.
    'social' => [
        'instagram' => env('SIYA_INSTAGRAM'),
        'facebook' => env('SIYA_FACEBOOK'),
        'tiktok' => env('SIYA_TIKTOK'),
    ],

    // Acompte demandé à la confirmation d'une commande (en %).
    'deposit_percent' => (int) env('SIYA_DEPOSIT_PERCENT', 50),

    // Secret des liens de suivi. Vide : dérivé de APP_KEY.
    'tracking_secret' => env('SIYA_TRACKING_SECRET'),

    // Limites
    'max_quantity' => 1000,
    'roster_max' => 80,
    'upload_max_kb' => 10240,
    'upload_max_files' => 5,
    'upload_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'svg', 'ai', 'eps', 'cdr', 'zip'],
];
