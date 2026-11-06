<script setup>
import { Link } from '@inertiajs/vue3';
import { priceLabel } from '../lib/product';
import Icon from './Icon.vue';
import ProductImage from './ProductImage.vue';

defineProps({ product: { type: Object, required: true } });
</script>

<template>
    <Link :href="`/produit/${product.slug}`" class="group flex flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-card transition hover:-translate-y-0.5 hover:border-gold-500 hover:shadow-lift">
        <div class="relative aspect-[4/3] overflow-hidden">
            <ProductImage :src="product.image" :name="product.name" :family="product.family" class="transition duration-500 group-hover:scale-105" />
            <span v-if="product.mode === 'quote'" class="absolute left-3 top-3 rounded-full bg-ink px-2.5 py-1 text-[11px] font-extrabold text-gold-300">Sur devis</span>
            <span v-else-if="product.featured" class="absolute left-3 top-3 rounded-full bg-gold-500 px-2.5 py-1 text-[11px] font-extrabold text-ink">Populaire</span>
        </div>
        <div class="flex flex-1 flex-col p-4">
            <span class="text-[11px] font-bold uppercase tracking-[.14em] text-muted">{{ product.familyLabel }}</span>
            <h3 class="mt-1 text-lg font-extrabold leading-snug">{{ product.name }}</h3>
            <p class="mt-1.5 line-clamp-2 flex-1 text-sm text-muted">{{ product.description }}</p>
            <div class="mt-4 flex items-center justify-between gap-2 border-t border-line pt-3">
                <strong class="font-display text-[15px]">{{ priceLabel(product) }}</strong>
                <span class="flex items-center gap-1 text-sm font-bold text-gold-700">Choisir <Icon name="arrow-right" :size="16" class="transition group-hover:translate-x-0.5" /></span>
            </div>
        </div>
    </Link>
</template>
