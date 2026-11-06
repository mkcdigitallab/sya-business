<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Families;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $family = Families::find($request->query('famille'))['slug'] ?? null;
        $search = trim((string) $request->query('q'));

        $products = Product::catalog()
            ->when($family, fn ($q) => $q->where('family', $family))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->get();

        return Inertia::render('Store/Shop', [
            'products' => $products->map->toStorefront()->values(),
            'filters' => ['famille' => $family, 'q' => $search],
            'counts' => Product::active()->selectRaw('family, count(*) as total')->groupBy('family')->pluck('total', 'family'),
        ]);
    }
}
