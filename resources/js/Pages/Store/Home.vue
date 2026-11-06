<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import ProductCard from '../../Components/ProductCard.vue';
import { familyIcon } from '../../lib/product';

defineProps({ featured: { type: Array, default: () => [] }, counts: { type: Object, default: () => ({}) } });

const page = usePage();
const families = computed(() => page.props.families);
const deposit = computed(() => page.props.site.depositPercent);

const steps = [
    { icon: 'search', title: 'Choisissez', text: 'Parcourez la boutique ou décrivez votre projet.' },
    { icon: 'ruler', title: 'Personnalisez', text: 'Tailles, noms, numéros, dimensions : tout se règle en ligne.' },
    { icon: 'eye', title: 'Validez', text: 'Nous confirmons le prix, puis vous validez la maquette.' },
    { icon: 'package', title: 'Recevez', text: 'Retrait à la boutique ou livraison, avec suivi en ligne.' },
];
const reasons = computed(() => [
    { icon: 'badge-check', title: 'Maquette validée avant impression', text: 'Vous voyez le résultat et vous donnez votre accord avant la production.' },
    { icon: 'receipt', title: 'Prix confirmé avant de commencer', text: 'Votre demande est chiffrée et confirmée avant toute fabrication.' },
    { icon: 'list-checks', title: 'Suivi en ligne, sans compte', text: 'Un lien personnel vous montre chaque étape de votre commande.' },
    { icon: 'wallet', title: 'Acompte simple', text: `${deposit.value} % à la confirmation (Wave, Orange Money ou espèces), le solde à la remise.` },
]);
</script>

<template>
    <Head title="Maillots, flocage, impression et création graphique" />

    <div class="space-y-20">
        <!-- Hero -->
        <section class="grid items-center gap-10 lg:grid-cols-[1.15fr_.85fr]">
            <div>
                <span class="eyebrow">Impression · Textile · Création</span>
                <h1 class="mt-4 text-[clamp(38px,6.4vw,66px)] font-extrabold leading-[1.02]">
                    Votre équipe,<br />votre marque,<br /><span class="text-gold-600">imprimées avec soin.</span>
                </h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-muted">
                    Maillots et flocage, textile personnalisé, bâches, vinyle, affiches, cartes de visite et création graphique : une seule adresse, de la maquette à la remise.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link href="/boutique" class="btn-gold">Voir la boutique <Icon name="arrow-right" :size="18" /></Link>
                    <Link href="/devis" class="btn-line">Demander un devis</Link>
                </div>
            </div>

            <div class="crop mx-auto w-full max-w-md">
                <div class="grid grid-cols-2 gap-3 rounded-2xl border border-line bg-white p-3 shadow-lift">
                    <Link v-for="f in families.slice(0, 4)" :key="f.slug" :href="`/boutique?famille=${f.slug}`"
                          class="halftone group flex aspect-square flex-col justify-between rounded-xl bg-gradient-to-br from-gold-50 to-gold-100 p-4 transition hover:from-gold-100 hover:to-gold-200">
                        <Icon :name="familyIcon(f.slug)" :size="34" :stroke="1.6" class="text-gold-700" />
                        <span><b class="block font-display text-[15px] leading-tight">{{ f.label }}</b><small class="mt-0.5 block text-xs text-muted">{{ f.tagline }}</small></span>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Expertises -->
        <section>
            <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                <div><span class="eyebrow">Nos expertises</span><h2 class="mt-2 text-3xl font-extrabold">Tout ce qu’il faut pour être vu</h2></div>
                <Link href="/boutique" class="flex items-center gap-1 text-sm font-bold text-gold-700 hover:underline">Toute la boutique <Icon name="arrow-right" :size="16" /></Link>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link v-for="f in families" :key="f.slug" :href="`/boutique?famille=${f.slug}`" class="card group flex gap-4 transition hover:-translate-y-0.5 hover:border-gold-500">
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl bg-gold-100 text-gold-700 transition group-hover:bg-gold-500 group-hover:text-ink"><Icon :name="familyIcon(f.slug)" :size="26" /></span>
                    <span class="min-w-0">
                        <b class="block font-display text-lg font-extrabold">{{ f.label }}</b>
                        <span class="mt-1 block text-sm leading-relaxed text-muted">{{ f.description }}</span>
                        <span class="mt-2 inline-block text-xs font-bold text-gold-700">{{ counts[f.slug] ?? 0 }} {{ (counts[f.slug] ?? 0) > 1 ? 'produits et services' : 'produit ou service' }}</span>
                    </span>
                </Link>
            </div>
        </section>

        <!-- Équipes -->
        <section class="halftone-dark relative overflow-hidden rounded-3xl bg-ink p-8 text-paper sm:p-12">
            <div class="relative grid items-center gap-8 md:grid-cols-[1.4fr_1fr]">
                <div>
                    <span class="eyebrow !text-gold-300 before:!bg-gold-400">Clubs, équipes, associations</span>
                    <h2 class="mt-3 text-3xl font-extrabold leading-tight sm:text-4xl">Une équipe à équiper ?<br />Envoyez votre liste de joueurs.</h2>
                    <p class="mt-4 max-w-lg text-paper/70">Nom, numéro, taille : saisissez chaque ligne une fois, nous nous occupons des maillots et du flocage. Vous recevez un prix clair et une maquette à valider.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link href="/produit/maillot-flocage" class="btn-gold">Composer ma liste <Icon name="arrow-right" :size="18" /></Link>
                        <Link href="/produit/flocage-nom-numero" class="btn border border-white/20 text-paper hover:bg-white/10">J’ai déjà mes maillots</Link>
                    </div>
                </div>
                <div class="hidden justify-center md:flex"><Icon name="shirt" :size="150" :stroke="1" class="text-gold-500/80" /></div>
            </div>
        </section>

        <!-- Populaires -->
        <section v-if="featured.length">
            <div class="mb-6"><span class="eyebrow">Les plus demandés</span><h2 class="mt-2 text-3xl font-extrabold">Pour bien démarrer</h2></div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"><ProductCard v-for="p in featured" :key="p.id" :product="p" /></div>
        </section>

        <!-- Comment ça marche -->
        <section>
            <div class="mb-8 text-center"><span class="eyebrow">Comment ça marche</span><h2 class="mt-2 text-3xl font-extrabold">De l’idée à la remise, en quatre étapes</h2></div>
            <ol class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <li v-for="(s, i) in steps" :key="s.title" class="card relative pt-9">
                    <span class="absolute -top-4 left-5 grid h-9 w-9 place-items-center rounded-full bg-gold-500 font-display text-sm font-extrabold text-ink ring-4 ring-paper">{{ i + 1 }}</span>
                    <Icon :name="s.icon" :size="26" class="text-gold-700" />
                    <h3 class="mt-3 text-lg font-extrabold">{{ s.title }}</h3>
                    <p class="mt-1 text-sm leading-relaxed text-muted">{{ s.text }}</p>
                </li>
            </ol>
            <p class="mt-6 text-center"><Link href="/informations" class="text-sm font-bold text-gold-700 hover:underline">Paiement, délais, annulation : tout savoir →</Link></p>
        </section>

        <!-- Pourquoi -->
        <section class="rounded-3xl border border-line bg-white p-6 sm:p-10">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="r in reasons" :key="r.title">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-gold-100 text-gold-700"><Icon :name="r.icon" :size="22" /></span>
                    <h3 class="mt-3 text-[17px] font-extrabold leading-snug">{{ r.title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-muted">{{ r.text }}</p>
                </div>
            </div>
        </section>

        <!-- Appel -->
        <section class="flex flex-col items-center gap-4 text-center">
            <h2 class="text-3xl font-extrabold">Un projet particulier ?</h2>
            <p class="max-w-xl text-muted">Infographie, logo, grand format, grosse quantité : décrivez-nous votre besoin, nous vous répondons avec un devis.</p>
            <div class="flex flex-wrap justify-center gap-3"><Link href="/devis" class="btn-dark">Demander un devis</Link><Link href="/contact" class="btn-line">Nous contacter</Link></div>
        </section>
    </div>
</template>
