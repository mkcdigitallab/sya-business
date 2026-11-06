<?php

namespace App\Support;

use App\Services\CartService;

class CartPresenter
{
    public static function make(CartService $cart): array
    {
        $items = collect($cart->items())->map(function (array $item) {
            $product = $item['product'];
            $r = $item['resolved'];

            return [
                'id' => $item['id'],
                'available' => $item['available'],
                'name' => $product?->name ?? 'Article indisponible',
                'slug' => $product?->slug,
                'image' => $product?->imageSrc(),
                'family' => $product?->family,
                'unitLabel' => $product?->unit_label,
                'mode' => $product?->pricing_mode,
                'editableQuantity' => $product && ! ($product->options['roster'] ?? null),
                'minQuantity' => $product?->min_quantity ?? 1,
                'quantity' => $r['quantity'] ?? 0,
                'unitPrice' => $r['unit_price'] ?? null,
                'lineTotal' => $r['line_total'] ?? null,
                'pending' => $r['pending'] ?? true,
                'summary' => $r['summary'] ?? [],
                'roster' => $r['config']['roster'] ?? [],
                'notes' => $r['config']['notes'] ?? null,
            ];
        })->values();

        $totals = $cart->totals();

        return [
            'items' => $items,
            'subtotal' => $totals['known'],
            'hasPending' => $totals['pending'],
            'hasUnavailable' => $items->contains(fn ($i) => ! $i['available']),
            'needsArtwork' => collect($cart->items())->contains(fn ($i) => $i['product']?->options['needs_artwork'] ?? false),
        ];
    }
}
