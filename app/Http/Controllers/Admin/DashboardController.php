<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Payment;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $byStatus = Order::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return Inertia::render('Admin/Dashboard', [
            // Chaque carte correspond à une action attendue de l'atelier.
            'todo' => [
                ['status' => 'received', 'label' => 'Demandes à traiter', 'count' => (int) ($byStatus['received'] ?? 0), 'hint' => 'Confirmer ou fixer le prix'],
                ['status' => 'quoted', 'label' => 'Devis envoyés', 'count' => (int) ($byStatus['quoted'] ?? 0), 'hint' => 'En attente de l’accord du client'],
                ['status' => 'proofing', 'label' => 'Maquettes à valider', 'count' => (int) ($byStatus['proofing'] ?? 0), 'hint' => 'En attente du client'],
                ['status' => 'confirmed', 'label' => 'À lancer', 'count' => (int) ($byStatus['confirmed'] ?? 0), 'hint' => 'Maquette ou production'],
                ['status' => 'production', 'label' => 'En production', 'count' => (int) ($byStatus['production'] ?? 0), 'hint' => 'À terminer'],
                ['status' => 'ready', 'label' => 'Prêtes', 'count' => (int) ($byStatus['ready'] ?? 0), 'hint' => 'À remettre au client'],
            ],
            'newInquiries' => Inquiry::where('status', 'new')->count(),
            'monthPayments' => (int) Payment::where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'recent' => Order::with(['items', 'payments'])->latest()->limit(8)->get()->map(fn (Order $o) => [
                'reference' => $o->reference,
                'customer' => $o->customer_name,
                'status' => $o->status->value,
                'statusLabel' => $o->status->label(),
                'total' => $o->total(),
                'pending' => $o->has_pending_price && $o->confirmed_total === null,
                'items' => $o->items->count(),
                'date' => $o->created_at->toIso8601String(),
            ])->values(),
        ]);
    }
}
