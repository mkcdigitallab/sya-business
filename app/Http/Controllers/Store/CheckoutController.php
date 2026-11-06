<?php

namespace App\Http\Controllers\Store;

use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\CartService;
use App\Services\OrderService;
use App\Support\CartPresenter;
use App\Support\TrackingTokens;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function show(CartService $cart): Response|RedirectResponse
    {
        if ($cart->count() === 0) {
            return redirect()->route('cart.index');
        }

        return Inertia::render('Store/Checkout', [
            'cart' => CartPresenter::make($cart),
            // Une référence par affichage du formulaire : un double clic ne crée jamais deux commandes.
            'clientReference' => 'SYCLIENT-'.Str::uuid(),
            'paymentMethods' => collect(PaymentMethod::forCustomers())->map(fn ($m) => ['value' => $m->value, 'label' => $m->label()])->values(),
            'paymentNumber' => config('siya.payment_number'),
            'limits' => ['files' => config('siya.upload_max_files'), 'sizeMb' => config('siya.upload_max_kb') / 1024, 'extensions' => config('siya.upload_extensions')],
        ]);
    }

    public function store(CheckoutRequest $request, OrderService $orders): RedirectResponse
    {
        try {
            [$order] = $orders->createFromCart($request->validated() + ['files' => $request->file('files', [])]);
        } catch (BusinessRuleException $e) {
            return back()->withErrors([$e->field => $e->getMessage()])->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tracking.show', ['reference' => $order->reference, 'token' => TrackingTokens::forOrder($order->reference)])
            ->with('success', 'Demande envoyée. Gardez bien cette page : elle vous permet de suivre votre commande.')
            ->with('justOrdered', true);
    }
}
