<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Icon from '../../../Components/Icon.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { formatShortDate, money, prettyPhone } from '../../../lib/format';

const props = defineProps({ orders: { type: Object, required: true }, filters: { type: Object, required: true }, statuses: { type: Array, required: true } });

const search = ref(props.filters.q);
let timer;
const go = (params) => router.get('/admin/commandes', params, { preserveState: true, preserveScroll: true, replace: true });
watch(search, (q) => { window.clearTimeout(timer); timer = window.setTimeout(() => go({ statut: props.filters.statut || undefined, q: q || undefined }), 300); });
</script>

<template>
    <Head title="Commandes" />

    <div class="space-y-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><span class="eyebrow">Atelier</span><h1 class="mt-1 text-3xl font-extrabold">Commandes</h1></div>
            <label class="relative block w-full sm:w-72">
                <Icon name="search" :size="17" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted" />
                <input v-model="search" type="search" class="input !pl-10" placeholder="Référence, nom, téléphone" aria-label="Rechercher une commande" />
            </label>
        </div>

        <div class="flex gap-2 overflow-x-auto pb-1">
            <Link href="/admin/commandes" class="chip shrink-0" :class="{ 'chip-on': !filters.statut }">Toutes</Link>
            <Link v-for="s in statuses" :key="s.value" :href="`/admin/commandes?statut=${s.value}`" class="chip shrink-0" :class="{ 'chip-on': filters.statut === s.value }">{{ s.label }}</Link>
        </div>

        <div class="card-flat overflow-x-auto !p-0">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-paper2 text-xs uppercase tracking-wider text-muted">
                    <tr><th class="px-4 py-3">Commande</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Articles</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3 text-right">Reste</th></tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="o in orders.data" :key="o.reference" class="transition hover:bg-gold-50">
                        <td class="px-4 py-3"><Link :href="`/admin/commandes/${o.reference}`" class="font-mono font-bold text-gold-700 hover:underline">{{ o.reference }}</Link><span class="block text-xs text-muted">{{ formatShortDate(o.date) }}</span></td>
                        <td class="px-4 py-3"><b>{{ o.customer }}</b><span class="block text-xs text-muted">{{ prettyPhone(o.phone) }}</span></td>
                        <td class="max-w-[260px] truncate px-4 py-3 text-muted" :title="o.items">{{ o.items }}</td>
                        <td class="px-4 py-3"><StatusBadge :status="o.status" :label="o.statusLabel" /></td>
                        <td class="px-4 py-3 text-right font-bold">{{ o.pending ? 'À chiffrer' : money(o.total) }}</td>
                        <td class="px-4 py-3 text-right" :class="o.balance ? 'font-bold' : 'text-muted'">{{ o.pending ? '—' : money(o.balance) }}</td>
                    </tr>
                    <tr v-if="!orders.data.length"><td colspan="6" class="px-4 py-12 text-center text-muted">Aucune commande.</td></tr>
                </tbody>
            </table>
        </div>

        <nav v-if="orders.last_page > 1" class="flex items-center justify-between text-sm" aria-label="Pagination">
            <Link v-if="orders.prev_page_url" :href="orders.prev_page_url" class="btn-line btn-sm" preserve-scroll>← Précédent</Link><span v-else />
            <span class="text-muted">Page {{ orders.current_page }} / {{ orders.last_page }}</span>
            <Link v-if="orders.next_page_url" :href="orders.next_page_url" class="btn-line btn-sm" preserve-scroll>Suivant →</Link><span v-else />
        </nav>
    </div>
</template>
