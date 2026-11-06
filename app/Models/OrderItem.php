<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'family', 'unit_label', 'quantity', 'unit_price', 'line_total', 'configuration',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'line_total' => 'integer',
            'configuration' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Lignes lisibles : « Taille : L », « Flocage nom & numéro »… (calculées à la commande). */
    public function summary(): array
    {
        return $this->configuration['summary'] ?? [];
    }

    public function roster(): array
    {
        return $this->configuration['roster'] ?? [];
    }
}
