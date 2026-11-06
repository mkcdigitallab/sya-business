<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderItem;

/**
 * Transforme une commande en tableau pour les pages Vue.
 * Une seule version pour le client (suivi) et pour l'atelier (admin) : seuls les champs internes et les
 * adresses des fichiers changent.
 */
class OrderPresenter
{
    public static function make(Order $order, bool $forAdmin = false): array
    {
        $order->loadMissing(['items', 'files', 'payments', 'events']);
        $token = TrackingTokens::forOrder($order->reference);

        $fileUrl = fn (OrderFile $f) => $forAdmin
            ? route('admin.files', ['order' => $order->reference, 'file' => $f->id])
            : route('tracking.file', ['reference' => $order->reference, 'file' => $f->id, 'token' => $token]);

        $mapFile = fn (OrderFile $f) => [
            'id' => $f->id,
            'name' => $f->original_name,
            'url' => $fileUrl($f),
            'isImage' => $f->isInlineImage(),
            'status' => $f->status,
            'comment' => $f->client_comment,
            'size' => $f->size,
            'date' => $f->created_at->toIso8601String(),
        ];

        $events = $order->events->when(! $forAdmin, fn ($e) => $e->where('visible_to_client', true));

        $data = [
            'reference' => $order->reference,
            'token' => $token,
            'status' => $order->status->value,
            'statusLabel' => $order->status->label(),
            'statusHelp' => $order->status->help(),
            'isFinal' => $order->status->isFinal(),
            'createdAt' => $order->created_at->toIso8601String(),
            'customer' => ['name' => $order->customer_name, 'phone' => $order->phone, 'email' => $order->email],
            'fulfillment' => $order->fulfillment,
            'address' => $order->address,
            'city' => $order->city,
            'notes' => $order->notes,
            'paymentPreference' => $order->payment_preference,
            'items' => $order->items->map(fn (OrderItem $i) => [
                'id' => $i->id,
                'name' => $i->product_name,
                'quantity' => $i->quantity,
                'unitLabel' => $i->unit_label,
                'unitPrice' => $i->unit_price,
                'lineTotal' => $i->line_total,
                'pending' => $i->line_total === null,
                'summary' => $i->summary(),
                'roster' => $i->roster(),
                'notes' => $i->configuration['notes'] ?? null,
            ])->values(),
            'estimatedTotal' => $order->estimated_total,
            'confirmedTotal' => $order->confirmed_total,
            'total' => $order->total(),
            'hasPendingPrice' => $order->has_pending_price,
            'quoteMessage' => $order->quote_message,
            'paid' => $order->paid(),
            'balance' => $order->balance(),
            'depositPercent' => $order->deposit_percent,
            'depositDue' => $order->depositDue(),
            'payments' => $order->payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'method' => $p->method->label(),
                'note' => $p->note,
                'date' => $p->paid_at->toIso8601String(),
            ])->values(),
            'artworks' => $order->files->where('kind', OrderFile::ARTWORK)->map($mapFile)->values(),
            'proofs' => $order->files->where('kind', OrderFile::PROOF)->map($mapFile)->values(),
            'events' => $events->map(fn ($e) => [
                'actor' => $e->actor,
                'message' => $e->message,
                'date' => $e->created_at->toIso8601String(),
            ])->values(),
        ];

        if ($forAdmin) {
            $data['internalNotes'] = $order->internal_notes;
            $data['nextStatuses'] = collect($order->status->next())
                ->reject(fn (OrderStatus $s) => $s === OrderStatus::Quoted)
                ->map(fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()])->values();
            $data['canSendQuote'] = in_array($order->status, [OrderStatus::Received, OrderStatus::Quoted], true);
            $data['canAddProof'] = in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Proofing], true);
        } else {
            $data['can'] = [
                'acceptQuote' => $order->status === OrderStatus::Quoted,
                'cancel' => $order->status->clientCanCancel(),
                'decideProof' => $order->status === OrderStatus::Proofing,
            ];
        }

        return $data;
    }
}
