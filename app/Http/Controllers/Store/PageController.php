<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /** « Comment ça marche » : étapes, paiement, délais, annulation (texte dans la page Vue). */
    public function info(): Response
    {
        return Inertia::render('Store/Info');
    }
}
