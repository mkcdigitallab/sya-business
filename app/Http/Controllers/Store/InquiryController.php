<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Product;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/** Demande de devis (infographie, projet sur mesure…) et message de contact : même mécanique, deux pages. */
class InquiryController extends Controller
{
    public function create(Request $request, string $type): Response
    {
        return Inertia::render($type === 'quote' ? 'Store/Quote' : 'Store/Contact', [
            'service' => $request->query('service'),
            'services' => Product::catalog()->get(['name', 'slug', 'family'])->map(fn ($p) => ['name' => $p->name, 'slug' => $p->slug])->values(),
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $request->merge(['phone' => PhoneNumber::normalize($request->input('phone'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', function ($attribute, $value, $fail) {
                if (! PhoneNumber::isValid($value)) {
                    $fail('Saisissez un numéro sénégalais valide, par exemple +221 77 123 45 67.');
                }
            }],
            'email' => ['nullable', 'email', 'max:190'],
            'service' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'message.min' => 'Décrivez votre demande en quelques mots (10 caractères minimum).',
        ]);

        Inquiry::create($data + [
            'reference' => ($type === 'quote' ? 'DV-' : 'CT-').now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'type' => $type,
        ]);

        return back()->with('success', $type === 'quote'
            ? 'Demande de devis envoyée. Nous vous répondons par téléphone.'
            : 'Message envoyé. Nous vous répondons rapidement.');
    }
}
