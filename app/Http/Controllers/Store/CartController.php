<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CartService;
use App\Support\CartPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(): Response
    {
        return Inertia::render('Store/Cart', ['cart' => CartPresenter::make($this->cart)]);
    }

    /** Ajoute un produit configuré. Les erreurs de personnalisation reviennent sous les bons champs du formulaire. */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['product' => ['required', 'string']]);

        $product = Product::active()->where('slug', $request->input('product'))->firstOrFail();
        $this->cart->add($product, $request->except('product'));

        return redirect()->route('cart.index')->with('success', "« {$product->name} » est dans votre panier.");
    }

    public function update(Request $request, string $line): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:'.config('siya.max_quantity')]]);
        $this->cart->updateQuantity($line, (int) $data['quantity']);

        return back();
    }

    public function destroy(string $line): RedirectResponse
    {
        $this->cart->remove($line);

        return back();
    }
}
