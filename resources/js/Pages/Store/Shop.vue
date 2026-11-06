<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import PageHeader from '../../Components/PageHeader.vue';
import ProductCard from '../../Components/ProductCard.vue';
import { familyIcon } from '../../lib/product';

const props = defineProps({
    products: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
});

const page = usePage();
const families = computed(() => page.props.families);
const current = computed(() => families.value.find((f) => f.slug === props.filters.famille));
const total = computed(() => Object.values(props.counts).reduce((a, b) => a + b, 0));

// La recherche relance la page (côté serveur) après une courte pause, sans perdre le focus du champ.
const search = ref(props.filters.q);
let timer;
watch(search, (value) => {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => {
        router.get('/boutique', { famille: props.filters.famille || undefined, q: value || undefined }, { preserveState: true, preserveScroll: true, replace: true, only: ['products', 'filters'] });
    }, 300);
});
</script>

<template>
    <Head :title="current ? current.label : 'Boutique'" />

    <div class="space-y-8">
        <PageHeader eyebrow="Boutique" :title="current ? current.label : 'Tous nos produits et services'" :subtitle="current ? current.description : 'Choisissez, personnalisez, validez : le prix définitif est confirmé avant la production.'" />

        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-1 lg:mx-0 lg:px-0 lg:pb-0">
                <Link href="/boutique" class="chip shrink-0 gap-2 !px-4" :class="{ 'chip-on': !filters.famille }">Tout <span class="text-xs text-muted">{{ total }}</span></Link>
                <Link v-for="f in families" :key="f.slug" :href="`/boutique?famille=${f.slug}`" class="chip shrink-0 gap-2 !px-4" :class="{ 'chip-on': filters.famille === f.slug }">
                    <Icon :name="familyIcon(f.slug)" :size="16" /> {{ f.label }} <span class="text-xs text-muted">{{ counts[f.slug] ?? 0 }}</span>
                </Link>
            </div>
            <label class="relative block lg:w-72">
                <Icon name="search" :size="18" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted" />
                <input v-model="search" type="search" class="input !pl-10" placeholder="Rechercher (maillot, bâche…)" aria-label="Rechercher un produit" />
            </label>
        </div>

        <div v-if="products.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <ProductCard v-for="p in products" :key="p.id" :product="p" />
        </div>
        <div v-else class="card flex flex-col items-center gap-3 py-14 text-center">
            <Icon name="search" :size="32" class="text-muted" />
            <h2 class="text-xl font-extrabold">Aucun résultat</h2>
            <p class="max-w-sm text-sm text-muted">Essayez un autre mot, ou décrivez-nous votre besoin : nous créons aussi sur mesure.</p>
            <div class="flex gap-2"><Link href="/boutique" class="btn-line btn-sm">Voir tout</Link><Link href="/devis" class="btn-dark btn-sm">Demander un devis</Link></div>
        </div>
    </div>
</template>
