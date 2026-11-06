<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Store/Home', [
            'featured' => Product::catalog()->where('is_featured', true)->limit(6)->get()->map->toStorefront()->values(),
            'counts' => Product::active()->selectRaw('family, count(*) as total')->groupBy('family')->pluck('total', 'family'),
        ]);
    }
}
