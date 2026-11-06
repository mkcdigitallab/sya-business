import { money } from './format';

/** Icône associée à chaque famille (clé = slug de App\Support\Families). */
export const FAMILY_ICONS = { maillots: 'shirt', textile: 'scissors', impression: 'printer', creation: 'pen-tool', mode: 'shopping-bag' };

export function familyIcon(slug) {
    return FAMILY_ICONS[slug] ?? 'package';
}

/** « 8 500 FCFA / pièce », « 12 000 FCFA / m² », « Sur devis »… */
export function priceLabel(product) {
    if (product.mode === 'quote') return product.price ? `À partir de ${money(product.price)}` : 'Sur devis';
    if (product.price === null) return 'Sur devis';
    return `${money(product.price)} / ${product.unitLabel}`;
}
