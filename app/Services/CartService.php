<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Panier en session. Chaque ligne a son propre identifiant : on peut donc commander le même maillot
 * avec deux personnalisations différentes (ce que l'ancien panier, indexé par produit, ne permettait pas).
 *
 * On ne garde en session que le choix du client ; les prix sont recalculés à chaque affichage.
 */
class CartService
{
    private const KEY = 'cart';

    public function __construct(private readonly Request $request, private readonly ProductConfigurator $configurator) {}

    /** @return array<string, array{product_id:int, input:array}> */
    private function raw(): array
    {
        return (array) $this->request->session()->get(self::KEY, []);
    }

    private function save(array $lines): void
    {
        $this->request->session()->put(self::KEY, $lines);
    }

    public function add(Product $product, array $input): string
    {
        $resolved = $this->configurator->resolve($product, $input); // lève une ValidationException si invalide

        $lines = $this->raw();
        $id = (string) Str::uuid();
        $lines[$id] = ['product_id' => $product->id, 'input' => $resolved['config']];
        $this->save($lines);

        return $id;
    }

    public function updateQuantity(string $lineId, int $quantity): void
    {
        $lines = $this->raw();
        if (! isset($lines[$lineId])) {
            return;
        }
        $product = Product::find($lines[$lineId]['product_id']);
        if (! $product || ($product->options['roster'] ?? null)) {
            return; // les lignes « liste de joueurs » se modifient en recomposant la liste
        }

        $input = ['quantity' => $quantity] + $lines[$lineId]['input'];
        $input['quantity'] = $quantity;
        try {
            $lines[$lineId]['input'] = $this->configurator->resolve($product, $input)['config'];
            $this->save($lines);
        } catch (ValidationException) {
            // quantité hors limites : on garde l'ancienne
        }
    }

    public function remove(string $lineId): void
    {
        $lines = $this->raw();
        unset($lines[$lineId]);
        $this->save($lines);
    }

    public function clear(): void
    {
        $this->request->session()->forget(self::KEY);
    }

    public function count(): int
    {
        return count($this->raw());
    }

    /**
     * Lignes prêtes à afficher ou à commander.
     *
     * @return list<array{id:string, product:?Product, available:bool, resolved:?array}>
     */
    public function items(): array
    {
        $raw = $this->raw();
        $products = Product::whereIn('id', collect($raw)->pluck('product_id'))->get()->keyBy('id');
        $items = [];

        foreach ($raw as $id => $line) {
            $product = $products[$line['product_id']] ?? null;
            $resolved = null;

            if ($product && $product->is_active) {
                try {
                    $resolved = $this->configurator->resolve($product, $line['input']);
                } catch (ValidationException) {
                    $resolved = null; // le catalogue a changé depuis l'ajout (taille retirée…)
                }
            }

            $items[] = ['id' => $id, 'product' => $product, 'available' => $resolved !== null, 'resolved' => $resolved];
        }

        return $items;
    }

    /** @return array{known:int, pending:bool, lines:int} */
    public function totals(): array
    {
        $known = 0;
        $pending = false;
        foreach ($this->items() as $item) {
            if (! $item['available']) {
                continue;
            }
            $item['resolved']['line_total'] === null
                ? $pending = true
                : $known += $item['resolved']['line_total'];
        }

        return ['known' => $known, 'pending' => $pending, 'lines' => count($this->raw())];
    }
}
