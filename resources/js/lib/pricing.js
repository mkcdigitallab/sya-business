/**
 * Aperçu du prix sur la fiche produit, calculé dans le navigateur pour réagir instantanément.
 * C'est une COPIE de app/Services/ProductConfigurator.php, uniquement pour l'affichage :
 * le prix réellement retenu est toujours recalculé par le serveur au moment de l'ajout au panier.
 */
export function estimate(product, form) {
    const o = product.options;
    const lines = [];
    let quantity = Number(form.quantity) || 0;

    if (o.roster) quantity = form.roster.length;

    let area = 1;
    if (product.mode === 'area') {
        const w = Number(form.width_cm) || 0;
        const h = Number(form.height_cm) || 0;
        area = (w * h) / 10000;
        if (w && h) lines.push(`${w} × ${h} cm (${area.toFixed(2).replace('.', ',')} m²)`);
    }

    let unitExtra = 0;
    let orderExtra = 0;

    for (const choice of o.choices) {
        const value = choice.values.find((v) => v.value === (form.choices[choice.key] ?? choice.values[0].value));
        unitExtra += Number(value?.price || 0);
    }
    for (const extra of o.extras) {
        if (extra.locked || form.extras.includes(extra.key)) {
            lines.push(extra.label);
            extra.per === 'order' ? (orderExtra += Number(extra.price || 0)) : (unitExtra += Number(extra.price || 0));
        }
    }

    const pending = product.mode === 'quote' || product.price === null;
    const unit = pending ? null : Math.round(product.price * area) + unitExtra;
    const total = unit === null ? null : unit * quantity + orderExtra;

    return { quantity, unit, total, pending, lines };
}
