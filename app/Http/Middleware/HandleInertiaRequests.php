<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use App\Support\Families;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /** Données partagées avec TOUTES les pages Vue. */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'site' => fn () => [
                'name' => config('app.name'),
                'phone' => config('siya.phone'),
                'email' => config('siya.email'),
                'address' => config('siya.address'),
                'hours' => config('siya.hours'),
                'social' => array_filter(config('siya.social')),
                'depositPercent' => config('siya.deposit_percent'),
            ],
            'families' => fn () => Families::all(),
            'cartCount' => fn () => app(CartService::class)->count(),
            'admin' => fn () => $request->user() ? ['name' => $request->user()->name] : null,
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
