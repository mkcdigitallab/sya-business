<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Passwords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        return $request->user() ? redirect()->route('admin.dashboard') : Inertia::render('Admin/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:128'],
        ]);

        $user = User::whereRaw('lower(email) = ?', [mb_strtolower($credentials['email'])])->where('is_active', true)->first();

        // On compare toujours un mot de passe (le faux hash si le compte n'existe pas) : le temps de réponse
        // ne révèle pas si l'adresse existe.
        $valid = Hash::check($credentials['password'], $user?->password ?: Passwords::DUMMY_HASH);

        if (! $user || ! $valid) {
            throw ValidationException::withMessages(['email' => 'Email ou mot de passe incorrect.']);
        }

        Auth::login($user, remember: false);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
