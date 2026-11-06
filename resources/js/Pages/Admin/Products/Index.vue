<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Icon from '../../../Components/Icon.vue';
import ProductImage from '../../../Components/ProductImage.vue';
import { money } from '../../../lib/format';

defineProps({ products: { type: Array, required: true } });
const modeLabel = { unit: 'à l’unité', area: 'au m²', pack: 'au pack', quote: 'sur devis' };
</script>

<template>
    <Head title="Catalogue" />

    <div class="space-y-5">
        <div><span class="eyebrow">Catalogue</span><h1 class="mt-1 text-3xl font-extrabold">Produits et services</h1><p class="mt-1 text-sm text-muted">Modifiez les prix, la visibilité et les photos. Les prix actuels sont indicatifs : validez-les avant la mise en ligne.</p></div>
        <div class="card-flat overflow-x-auto !p-0">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-paper2 text-xs uppercase tracking-wider text-muted"><tr><th class="px-4 py-3">Produit</th><th class="px-4 py-3">Famille</th><th class="px-4 py-3">Prix</th><th class="px-4 py-3">Visible</th><th class="px-4 py-3" /></tr></thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="p in products" :key="p.slug" :class="{ 'opacity-60': !p.isActive }">
                        <td class="px-4 py-3"><div class="flex items-center gap-3"><span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg"><ProductImage :src="p.image" :name="p.name" :icon-size="18" /></span><b>{{ p.name }}</b><span v-if="p.isFeatured" class="rounded-full bg-gold-100 px-2 py-0.5 text-[11px] font-extrabold text-gold-800">Populaire</span></div></td>
                        <td class="px-4 py-3 text-muted">{{ p.family }}</td>
                        <td class="px-4 py-3 font-bold">{{ p.price !== null ? money(p.price) : '—' }} <span class="font-normal text-muted">{{ modeLabel[p.mode] }}</span></td>
                        <td class="px-4 py-3"><span :class="p.isActive ? 'text-ok' : 'text-muted'" class="font-bold">{{ p.isActive ? 'Oui' : 'Masqué' }}</span></td>
                        <td class="px-4 py-3 text-right"><Link :href="`/admin/produits/${p.slug}`" class="btn-line btn-sm"><Icon name="pencil" :size="14" /> Modifier</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
