<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { formatShortDate, money } from '../../lib/format';

defineProps({
    todo: { type: Array, required: true },
    newInquiries: { type: Number, default: 0 },
    monthPayments: { type: Number, default: 0 },
    recent: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Tableau de bord" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><span class="eyebrow">Atelier</span><h1 class="mt-1 text-3xl font-extrabold">Tableau de bord</h1></div>
            <div class="flex gap-3 text-sm">
                <div class="rounded-xl border border-line bg-white px-4 py-2"><span class="block text-xs text-muted">Encaissé ce mois-ci</span><b class="font-display text-lg">{{ money(monthPayments) }}</b></div>
                <Link href="/admin/demandes?statut=new" class="rounded-xl border px-4 py-2" :class="newInquiries ? 'border-gold-500 bg-gold-100' : 'border-line bg-white'"><span class="block text-xs text-muted">Demandes à rappeler</span><b class="font-display text-lg">{{ newInquiries }}</b></Link>
            </div>
        </div>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="t in todo" :key="t.status" :href="`/admin/commandes?statut=${t.status}`" class="card-flat group transition hover:border-gold-500" :class="t.count ? 'bg-white' : 'opacity-70'">
                <div class="flex items-start justify-between"><span class="text-sm font-bold">{{ t.label }}</span><b class="font-display text-3xl font-extrabold" :class="t.count ? 'text-gold-700' : 'text-muted'">{{ t.count }}</b></div>
                <p class="mt-1 text-xs text-muted">{{ t.hint }}</p>
            </Link>
        </section>

        <section class="card-flat !p-0">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="font-display text-lg font-extrabold">Dernières commandes</h2>
                <Link href="/admin/commandes" class="flex items-center gap-1 text-sm font-bold text-gold-700 hover:underline">Tout voir <Icon name="arrow-right" :size="15" /></Link>
            </div>
            <p v-if="!recent.length" class="px-5 py-10 text-center text-sm text-muted">Aucune commande pour le moment.</p>
            <ul class="divide-y divide-line">
                <li v-for="o in recent" :key="o.reference">
                    <Link :href="`/admin/commandes/${o.reference}`" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3.5 transition hover:bg-gold-50">
                        <b class="w-40 font-mono text-sm">{{ o.reference }}</b>
                        <span class="min-w-0 flex-1 truncate font-semibold">{{ o.customer }}</span>
                        <span class="text-xs text-muted">{{ formatShortDate(o.date) }}</span>
                        <StatusBadge :status="o.status" :label="o.statusLabel" />
                        <strong class="w-32 text-right font-display">{{ o.pending ? 'À chiffrer' : money(o.total) }}</strong>
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
