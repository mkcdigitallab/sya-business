<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

/**
 * Valide la personnalisation demandée pour un produit ET calcule son prix.
 *
 * C'est le seul endroit qui connaît les règles de prix. Le navigateur envoie des choix (taille, dimensions,
 * liste de joueurs…) ; le serveur recalcule tout à partir du catalogue. Un prix envoyé par le navigateur
 * n'est jamais pris en compte.
 *
 * Modes de prix (colonne products.pricing_mode) :
 *   unit  → prix × quantité                         (T-shirt, polo, maillot, flocage…)
 *   area  → prix du m² × surface × quantité         (bâche, vinyle)
 *   pack  → prix du pack × nombre de packs          (cartes de visite : 1 pack = 100 cartes)
 *   quote → pas de prix automatique : sur devis     (infographie, logo…)
 *
 * Options (colonne products.options) : sizes, size_label, extras, choices, roster, dimensions, pack_size,
 * needs_artwork, note_hint — voir database/seeders/ProductSeeder.php pour des exemples.
 */
class ProductConfigurator
{
    /**
     * @param  array<string,mixed>  $input  données brutes du formulaire
     * @return array{quantity:int, unit_price:?int, line_total:?int, pending:bool, summary:list<string>, config:array<string,mixed>}
     *
     * @throws ValidationException
     */
    public function resolve(Product $product, array $input): array
    {
        $o = $product->options ?? [];
        $errors = [];
        $summary = [];
        $config = [];

        // ── 1. Quantité (ou liste de joueurs) ─────────────────────────────────────────────
        $roster = $o['roster'] ?? null;
        $sizes = $o['sizes'] ?? [];
        $sizeInRoster = $roster && in_array('size', $roster['fields'] ?? [], true);

        if ($roster) {
            [$lines, $rosterErrors] = $this->cleanRoster((array) ($input['roster'] ?? []), $roster, $sizes);
            $errors += $rosterErrors;
            $config['roster'] = $lines;
            $quantity = count($lines);
            if ($quantity) {
                $summary[] = $quantity.' '.($quantity > 1 ? 'lignes' : 'ligne').' sur la liste';
            }
        } else {
            $quantity = (int) ($input['quantity'] ?? 0);
            $min = max(1, (int) $product->min_quantity);
            $max = (int) config('siya.max_quantity');
            if ($quantity < $min || $quantity > $max) {
                $errors['quantity'] = $min > 1
                    ? "La quantité doit être comprise entre {$min} et {$max}."
                    : "La quantité doit être comprise entre 1 et {$max}.";
            }
            $config['quantity'] = $quantity;
        }

        // ── 2. Taille (si elle n'est pas déjà saisie ligne par ligne dans la liste) ───────────
        if ($sizes && ! $sizeInRoster) {
            $size = (string) ($input['size'] ?? '');
            if (! in_array($size, $sizes, true)) {
                $errors['size'] = 'Choisissez une option.';
            } else {
                $config['size'] = $size;
                $summary[] = ($o['size_label'] ?? 'Taille').' : '.$size;
            }
        }

        // ── 3. Choix à sélection unique (format, impression recto/verso…) ─────────────────────
        $unitExtra = 0;
        $orderExtra = 0;
        foreach ($o['choices'] ?? [] as $choice) {
            $key = $choice['key'];
            $selected = (string) (($input['choices'] ?? [])[$key] ?? $choice['values'][0]['value']);
            $value = collect($choice['values'])->firstWhere('value', $selected);
            if (! $value) {
                $errors["choices.{$key}"] = 'Choisissez une option.';

                continue;
            }
            $config['choices'][$key] = $selected;
            $summary[] = $choice['label'].' : '.$value['label'];
            $unitExtra += (int) ($value['price'] ?? 0);
        }

        // ── 4. Options à cocher ───────────────────────────────────────────────────────────
        $wanted = array_map('strval', (array) ($input['extras'] ?? []));
        $extras = [];
        foreach ($o['extras'] ?? [] as $extra) {
            if (! empty($extra['locked']) || in_array($extra['key'], $wanted, true)) {
                $extras[] = $extra['key'];
                $summary[] = $extra['label'];
                if (($extra['per'] ?? 'unit') === 'order') {
                    $orderExtra += (int) ($extra['price'] ?? 0);
                } else {
                    $unitExtra += (int) ($extra['price'] ?? 0);
                }
            }
        }
        if ($extras) {
            $config['extras'] = $extras;
        }

        // ── 5. Dimensions (bâche, vinyle) ─────────────────────────────────────────────────
        $area = 1.0;
        if ($product->pricing_mode === 'area') {
            $dim = $o['dimensions'] ?? ['min_cm' => 10, 'max_cm' => 1000];
            $w = (int) ($input['width_cm'] ?? 0);
            $h = (int) ($input['height_cm'] ?? 0);
            foreach (['width_cm' => $w, 'height_cm' => $h] as $field => $value) {
                if ($value < $dim['min_cm'] || $value > $dim['max_cm']) {
                    $errors[$field] = "Saisissez une valeur entre {$dim['min_cm']} et {$dim['max_cm']} cm.";
                }
            }
            if (! isset($errors['width_cm'], $errors['height_cm'])) {
                $area = ($w * $h) / 10000;
                $config['width_cm'] = $w;
                $config['height_cm'] = $h;
                $summary[] = "Dimensions : {$w} × {$h} cm (".number_format($area, 2, ',', ' ').' m²)';
            }
        }

        if ($product->pricing_mode === 'pack' && ! empty($o['pack_size']) && $quantity > 0) {
            $summary[] = $quantity.' '.($quantity > 1 ? 'packs' : 'pack').' de '.$o['pack_size'].' = '.($quantity * $o['pack_size']).' exemplaires';
        }

        // ── 6. Note libre ─────────────────────────────────────────────────────────────────
        $notes = trim((string) ($input['notes'] ?? ''));
        if (mb_strlen($notes) > 500) {
            $errors['notes'] = 'Votre précision est trop longue (500 caractères maximum).';
        } elseif ($notes !== '') {
            $config['notes'] = $notes;
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        // ── 7. Prix ───────────────────────────────────────────────────────────────────────
        $unit = null;
        if ($product->pricing_mode !== 'quote' && $product->price !== null) {
            $unit = (int) round($product->price * $area) + $unitExtra;
        }
        $total = $unit !== null ? $unit * $quantity + $orderExtra : null;

        return [
            'quantity' => $quantity,
            'unit_price' => $unit,
            'line_total' => $total,
            'pending' => $unit === null,
            'summary' => $summary,
            'config' => $config,
        ];
    }

    /**
     * Nettoie la liste « nom / numéro / taille » (équipes de football, associations…).
     *
     * @return array{0: list<array<string,string>>, 1: array<string,string>}
     */
    private function cleanRoster(array $lines, array $roster, array $sizes): array
    {
        $fields = $roster['fields'] ?? [];
        $required = $roster['required'] ?? [];
        $errors = [];
        $clean = [];

        $lines = array_values(array_filter($lines, 'is_array'));
        $max = (int) config('siya.roster_max');

        if (! $lines) {
            return [[], ['roster' => 'Ajoutez au moins une ligne à votre liste.']];
        }
        if (count($lines) > $max) {
            return [[], ['roster' => "La liste est limitée à {$max} lignes : contactez-nous pour une commande plus importante."]];
        }

        foreach ($lines as $i => $line) {
            $row = [];

            if (in_array('name', $fields, true)) {
                $name = mb_strtoupper(mb_substr(trim((string) ($line['name'] ?? '')), 0, 20));
                if ($name === '' && in_array('name', $required, true)) {
                    $errors["roster.{$i}.name"] = 'Nom requis.';
                }
                $row['name'] = $name;
            }

            if (in_array('number', $fields, true)) {
                $number = trim((string) ($line['number'] ?? ''));
                if ($number === '' && in_array('number', $required, true)) {
                    $errors["roster.{$i}.number"] = 'Numéro requis.';
                } elseif ($number !== '' && ! preg_match('/^\d{1,3}$/', $number)) {
                    $errors["roster.{$i}.number"] = 'Numéro invalide (chiffres uniquement).';
                }
                $row['number'] = $number;
            }

            if (in_array('size', $fields, true)) {
                $size = (string) ($line['size'] ?? '');
                if (! in_array($size, $sizes, true)) {
                    $errors["roster.{$i}.size"] = 'Taille requise.';
                }
                $row['size'] = $size;
            }

            $clean[] = $row;
        }

        return [$clean, $errors];
    }
}
