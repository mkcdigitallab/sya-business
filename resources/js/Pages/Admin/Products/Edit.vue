<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Icon from '../../../Components/Icon.vue';

const props = defineProps({ product: { type: Object, required: true } });
const p = props.product;

const form = useForm({
    name: p.name, description: p.description, unit_label: p.unitLabel, price: p.price ?? '', min_quantity: p.minQuantity,
    lead_time_days: p.leadTimeDays ?? '', sort_order: p.sortOrder, is_active: p.isActive, is_featured: p.isFeatured,
    sizes: p.sizes, image: null, remove_image: false,
});

const priceHint = { unit: 'Prix d’une pièce, en FCFA.', area: 'Prix du mètre carré, en FCFA.', pack: 'Prix d’un pack, en FCFA.', quote: 'Laissez vide pour « sur devis », ou saisissez un prix « à partir de ».' }[p.mode];
// L'envoi d'un fichier passe par POST : Laravel ne lit pas les fichiers d'une requête PATCH.
const submit = () => form.post(`/admin/produits/${p.slug}`, { forceFormData: true });
</script>

<template>
    <Head :title="`Modifier ${product.name}`" />

    <div class="mx-auto max-w-3xl space-y-5">
        <div>
            <Link href="/admin/produits" class="mb-2 inline-flex items-center gap-1.5 text-sm font-bold text-muted hover:text-ink"><Icon name="arrow-left" :size="15" /> Catalogue</Link>
            <h1 class="text-3xl font-extrabold">{{ product.name }}</h1><p class="text-sm text-muted">{{ product.family }}</p>
        </div>

        <form class="card space-y-5" novalidate @submit.prevent="submit">
            <div><label class="label" for="name">Nom</label><input id="name" v-model="form.name" class="input" :class="{ 'input-error': form.errors.name }" /><p v-if="form.errors.name" class="error">{{ form.errors.name }}</p></div>
            <div><label class="label" for="desc">Description</label><textarea id="desc" v-model="form.description" rows="4" class="input" :class="{ 'input-error': form.errors.description }" /><p v-if="form.errors.description" class="error">{{ form.errors.description }}</p></div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label" for="price">Prix (FCFA)</label><input id="price" v-model="form.price" type="number" min="0" class="input" :class="{ 'input-error': form.errors.price }" /><p class="hint">{{ priceHint }}</p><p v-if="form.errors.price" class="error">{{ form.errors.price }}</p></div>
                <div><label class="label" for="unit">Unité affichée</label><input id="unit" v-model="form.unit_label" class="input" /><p class="hint">Ex. pièce, m², pack de 100.</p></div>
                <div><label class="label" for="minq">Quantité minimale</label><input id="minq" v-model="form.min_quantity" type="number" min="1" class="input" /></div>
                <div><label class="label" for="lead">Délai indicatif (jours)</label><input id="lead" v-model="form.lead_time_days" type="number" min="0" class="input" /><p class="hint">Laissez vide pour ne rien afficher.</p></div>
            </div>

            <div><label class="label" for="sizes">Tailles proposées</label><input id="sizes" v-model="form.sizes" class="input" placeholder="S, M, L, XL" /><p class="hint">Séparées par des virgules. Vide = pas de choix de taille.</p></div>

            <div>
                <label class="label">Photo</label>
                <div class="flex flex-wrap items-center gap-4">
                    <span class="grid h-24 w-24 place-items-center overflow-hidden rounded-xl bg-gold-100 text-gold-700"><img v-if="product.image && !form.remove_image" :src="product.image" alt="" class="h-full w-full object-cover" /><Icon v-else name="image" :size="30" /></span>
                    <div class="space-y-2">
                        <input type="file" accept=".jpg,.jpeg,.png,.webp" class="block text-sm" @change="form.image = $event.target.files[0]" />
                        <label v-if="product.hasUploadedImage" class="flex items-center gap-2 text-sm text-muted"><input v-model="form.remove_image" type="checkbox" class="rounded border-line text-gold-600 focus:ring-gold-500" /> Retirer la photo envoyée</label>
                    </div>
                </div>
                <p v-if="form.errors.image" class="error">{{ form.errors.image }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <label class="flex items-center gap-3 rounded-xl border border-line px-4 py-3 text-sm font-semibold"><input v-model="form.is_active" type="checkbox" class="rounded border-line text-gold-600 focus:ring-gold-500" /> Visible sur le site</label>
                <label class="flex items-center gap-3 rounded-xl border border-line px-4 py-3 text-sm font-semibold"><input v-model="form.is_featured" type="checkbox" class="rounded border-line text-gold-600 focus:ring-gold-500" /> Populaire</label>
                <div><input v-model="form.sort_order" type="number" min="0" class="input" aria-label="Ordre d’affichage" /><p class="hint">Ordre d’affichage (plus petit = plus haut).</p></div>
            </div>

            <div class="flex gap-3"><button class="btn-gold" :disabled="form.processing">Enregistrer</button><Link href="/admin/produits" class="btn-line">Annuler</Link></div>
        </form>
    </div>
</template>
