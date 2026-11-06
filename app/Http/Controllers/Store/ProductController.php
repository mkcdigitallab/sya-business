<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        return Inertia::render('Store/Show', [
            'product' => $product->toStorefront(),
            'related' => Product::catalog()->where('family', $product->family)->whereKeyNot($product->id)->limit(3)->get()->map->toStorefront()->values(),
        ]);
    }
}
