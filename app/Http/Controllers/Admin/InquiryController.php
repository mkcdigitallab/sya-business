<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function index(Request $request): Response
    {
        $status = in_array($request->query('statut'), ['new', 'contacted', 'closed'], true) ? $request->query('statut') : null;

        return Inertia::render('Admin/Inquiries', [
            'inquiries' => Inquiry::when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(20)->withQueryString()
                ->through(fn (Inquiry $i) => [
                    'id' => $i->id,
                    'reference' => $i->reference,
                    'type' => $i->type,
                    'name' => $i->name,
                    'phone' => $i->phone,
                    'email' => $i->email,
                    'service' => $i->service,
                    'message' => $i->message,
                    'status' => $i->status,
                    'notes' => $i->staff_notes,
                    'date' => $i->created_at->toIso8601String(),
                ]),
            'filters' => ['statut' => $status],
        ]);
    }

    public function update(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'contacted', 'closed'])],
            'staff_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $inquiry->update($data);

        return back()->with('success', 'Demande mise à jour.');
    }
}
