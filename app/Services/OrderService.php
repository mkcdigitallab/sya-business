<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\OrderFile;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(private readonly CartService $cart, private readonly OrderFiles $files) {}

    /**
     * Transforme le panier en demande de commande.
     * Idempotent : rejouer la même `client_reference` (double clic) renvoie la commande déjà créée.
     *
     * @param  array<string,mixed>  $data  champs validés par CheckoutRequest
     * @return array{0: Order, 1: bool}  [commande, déjà existante ?]
     */
    public function createFromCart(array $data): array
    {
        $existing = Order::where('client_reference', $data['client_reference'])->first();
        if ($existing) {
            return [$existing, true];
        }

        $items = $this->cart->items();
        if (! $items) {
            throw new BusinessRuleException('Votre panier est vide.');
        }
        foreach ($items as $item) {
            if (! $item['available']) {
                throw new BusinessRuleException('Un article de votre panier n’est plus disponible : retirez-le pour continuer.');
            }
        }

        try {
            $order = DB::transaction(function () use ($data, $items) {
                $order = Order::create([
                    'reference' => $this->newReference(),
                    'client_reference' => $data['client_reference'],
                    'customer_name' => $data['customer_name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?: null,
                    'fulfillment' => $data['fulfillment'],
                    'address' => $data['fulfillment'] === 'delivery' ? $data['address'] : null,
                    'city' => $data['fulfillment'] === 'delivery' ? $data['city'] : null,
                    'notes' => $data['notes'] ?: null,
                    'payment_preference' => $data['payment_preference'] ?? null,
                    'status' => OrderStatus::Received,
                    'deposit_percent' => config('siya.deposit_percent'),
                ]);

                $known = 0;
                $pending = false;
                foreach ($items as $item) {
                    $r = $item['resolved'];
                    $product = $item['product'];
                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'family' => $product->family,
                        'unit_label' => $product->unit_label,
                        'quantity' => $r['quantity'],
                        'unit_price' => $r['unit_price'],
                        'line_total' => $r['line_total'],
                        'configuration' => $r['config'] + ['summary' => $r['summary']],
                    ]);
                    $r['line_total'] === null ? $pending = true : $known += $r['line_total'];
                }
                $order->update(['estimated_total' => $known, 'has_pending_price' => $pending]);

                $order->events()->create(['actor' => 'client', 'message' => 'Demande envoyée.']);

                if (! empty($data['files'])) {
                    $this->files->store($order, $data['files'], OrderFile::ARTWORK);
                }

                return $order;
            });
        } catch (UniqueConstraintViolationException) {
            // Deux requêtes simultanées avec la même référence : la seconde retrouve la première.
            return [Order::where('client_reference', $data['client_reference'])->firstOrFail(), true];
        }

        $this->cart->clear();

        return [$order, false];
    }

    private function newReference(): string
    {
        do {
            $reference = 'SY-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (Order::where('reference', $reference)->exists());

        return $reference;
    }
}
