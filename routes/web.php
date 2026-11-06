<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Store;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Boutique (visiteurs, sans compte)
|--------------------------------------------------------------------------
| Plan du site : accueil → boutique (par famille) → fiche produit / configurateur → panier → commande
| → suivi. À côté : devis, contact, informations.
*/
Route::get('/', Store\HomeController::class)->name('home');
Route::get('/boutique', [Store\ShopController::class, 'index'])->name('shop');
Route::get('/produit/{product}', [Store\ProductController::class, 'show'])->name('products.show');

Route::get('/panier', [Store\CartController::class, 'index'])->name('cart.index');
Route::post('/panier', [Store\CartController::class, 'store'])->name('cart.store');
Route::patch('/panier/{line}', [Store\CartController::class, 'update'])->name('cart.update');
Route::delete('/panier/{line}', [Store\CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/commande', [Store\CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/commande', [Store\CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');

Route::middleware('throttle:track')->prefix('suivi/{reference}')->group(function () {
    Route::get('/', [Store\TrackingController::class, 'show'])->name('tracking.show');
    Route::post('/devis', [Store\TrackingController::class, 'acceptQuote'])->name('tracking.quote.accept');
    Route::post('/maquettes/{file}', [Store\TrackingController::class, 'decideProof'])->name('tracking.proof.decide');
    Route::post('/annuler', [Store\TrackingController::class, 'cancel'])->name('tracking.cancel');
    Route::get('/fichiers/{file}', [Store\TrackingController::class, 'file'])->name('tracking.file');
});

Route::get('/devis', [Store\InquiryController::class, 'create'])->defaults('type', 'quote')->name('quote.create');
Route::post('/devis', [Store\InquiryController::class, 'store'])->defaults('type', 'quote')->middleware('throttle:inquiry')->name('quote.store');
Route::get('/contact', [Store\InquiryController::class, 'create'])->defaults('type', 'contact')->name('contact.create');
Route::post('/contact', [Store\InquiryController::class, 'store'])->defaults('type', 'contact')->middleware('throttle:inquiry')->name('contact.store');
Route::get('/informations', [Store\PageController::class, 'info'])->name('info');

/*
|--------------------------------------------------------------------------
| Administration : /admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::get('/connexion', [Admin\AuthController::class, 'show'])->name('admin.login');
    Route::post('/connexion', [Admin\AuthController::class, 'login'])->middleware('throttle:login')->name('admin.login.store');

    Route::middleware(['auth', 'admin', 'throttle:admin'])->group(function () {
        Route::post('/deconnexion', [Admin\AuthController::class, 'logout'])->name('admin.logout');
        Route::get('/', Admin\DashboardController::class)->name('admin.dashboard');

        Route::get('/commandes', [Admin\OrderController::class, 'index'])->name('admin.orders.index');
        Route::get('/commandes/{order}', [Admin\OrderController::class, 'show'])->name('admin.orders.show');
        Route::patch('/commandes/{order}/statut', [Admin\OrderController::class, 'status'])->name('admin.orders.status');
        Route::post('/commandes/{order}/devis', [Admin\OrderController::class, 'quote'])->name('admin.orders.quote');
        Route::post('/commandes/{order}/maquettes', [Admin\OrderController::class, 'proof'])->name('admin.orders.proof');
        Route::post('/commandes/{order}/paiements', [Admin\OrderController::class, 'payment'])->name('admin.orders.payment');
        Route::patch('/commandes/{order}/notes', [Admin\OrderController::class, 'notes'])->name('admin.orders.notes');
        Route::get('/commandes/{order}/fichiers/{file}', [Admin\OrderController::class, 'file'])->name('admin.files');

        Route::get('/produits', [Admin\ProductController::class, 'index'])->name('admin.products.index');
        Route::get('/produits/{product}', [Admin\ProductController::class, 'edit'])->name('admin.products.edit');
        Route::post('/produits/{product}', [Admin\ProductController::class, 'update'])->name('admin.products.update');

        Route::get('/demandes', [Admin\InquiryController::class, 'index'])->name('admin.inquiries.index');
        Route::patch('/demandes/{inquiry}', [Admin\InquiryController::class, 'update'])->name('admin.inquiries.update');
    });
});
