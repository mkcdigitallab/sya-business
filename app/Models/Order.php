<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'reference', 'client_reference', 'customer_name', 'phone', 'email', 'fulfillment', 'address', 'city', 'notes',
        'payment_preference', 'status', 'estimated_total', 'has_pending_price', 'confirmed_total', 'quote_message',
        'deposit_percent', 'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'estimated_total' => 'integer',
            'confirmed_total' => 'integer',
            'deposit_percent' => 'integer',
            'has_pending_price' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('id');
    }

    /** Prix retenu : celui fixé par l'atelier, sinon l'estimation affichée à la commande. */
    public function total(): int
    {
        return (int) ($this->confirmed_total ?? $this->estimated_total);
    }

    /** Le prix n'est pas définitif tant que l'atelier ne l'a pas confirmé (ou qu'une ligne reste sur devis). */
    public function isPriceFinal(): bool
    {
        return $this->confirmed_total !== null || ! $this->has_pending_price && $this->status !== OrderStatus::Received;
    }

    public function paid(): int
    {
        return (int) $this->payments->sum('amount');
    }

    public function balance(): int
    {
        return max(0, $this->total() - $this->paid());
    }

    public function depositDue(): int
    {
        return (int) ceil($this->total() * $this->deposit_percent / 100);
    }
}
