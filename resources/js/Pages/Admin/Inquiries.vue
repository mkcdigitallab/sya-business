<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import { formatDate, prettyPhone } from '../../lib/format';

defineProps({ inquiries: { type: Object, required: true }, filters: { type: Object, required: true } });

const labels = { new: 'Nouvelle', contacted: 'Rappelée', closed: 'Clôturée' };
const tabs = [['', 'Toutes'], ['new', 'Nouvelles'], ['contacted', 'Rappelées'], ['closed', 'Clôturées']];
const notes = ref({});
const setStatus = (i, status) => router.patch(`/admin/demandes/${i.id}`, { status, staff_notes: notes.value[i.id] ?? i.notes ?? '' }, { preserveScroll: true });
</script>

<template>
    <Head title="Demandes" />

    <div class="space-y-5">
        <div><span class="eyebrow">Atelier</span><h1 class="mt-1 text-3xl font-extrabold">Demandes de devis et messages</h1></div>
        <div class="flex gap-2 overflow-x-auto pb-1"><Link v-for="[v, l] in tabs" :key="v" :href="v ? `/admin/demandes?statut=${v}` : '/admin/demandes'" class="chip shrink-0" :class="{ 'chip-on': (filters.statut ?? '') === v }">{{ l }}</Link></div>

        <p v-if="!inquiries.data.length" class="card-flat py-12 text-center text-muted">Aucune demande.</p>

        <article v-for="i in inquiries.data" :key="i.id" class="card-flat space-y-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><b class="text-lg">{{ i.name }}</b><span class="ml-2 rounded-full px-2 py-0.5 text-[11px] font-extrabold" :class="i.type === 'quote' ? 'bg-gold-100 text-gold-800' : 'bg-sky-100 text-sky-800'">{{ i.type === 'quote' ? 'Devis' : 'Contact' }}</span>
                    <p class="text-xs text-muted">{{ i.reference }} · {{ formatDate(i.date) }}<template v-if="i.service"> · {{ i.service }}</template></p></div>
                <span class="rounded-full px-2.5 py-1 text-xs font-extrabold" :class="i.status === 'new' ? 'bg-gold-500 text-ink' : i.status === 'closed' ? 'bg-stone-200 text-stone-700' : 'bg-emerald-100 text-emerald-800'">{{ labels[i.status] }}</span>
            </div>
            <p class="whitespace-pre-line rounded-xl bg-paper2 p-3 text-sm">{{ i.message }}</p>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a :href="`tel:${i.phone}`" class="btn-dark btn-sm"><Icon name="phone" :size="14" /> {{ prettyPhone(i.phone) }}</a>
                <a v-if="i.email" :href="`mailto:${i.email}`" class="btn-line btn-sm"><Icon name="mail" :size="14" /> {{ i.email }}</a>
            </div>
            <div class="flex flex-wrap items-center gap-2 border-t border-line pt-3">
                <input v-model="notes[i.id]" class="input !py-2 flex-1" :placeholder="i.notes || 'Note interne'" aria-label="Note interne" />
                <button v-if="i.status !== 'contacted'" class="btn-line btn-sm" @click="setStatus(i, 'contacted')">Rappelée</button>
                <button v-if="i.status !== 'closed'" class="btn-line btn-sm" @click="setStatus(i, 'closed')">Clôturer</button>
                <button v-if="i.status !== 'new'" class="btn-line btn-sm" @click="setStatus(i, 'new')">Rouvrir</button>
            </div>
        </article>

        <nav v-if="inquiries.last_page > 1" class="flex items-center justify-between text-sm">
            <Link v-if="inquiries.prev_page_url" :href="inquiries.prev_page_url" class="btn-line btn-sm">← Précédent</Link><span v-else />
            <Link v-if="inquiries.next_page_url" :href="inquiries.next_page_url" class="btn-line btn-sm">Suivant →</Link>
        </nav>
    </div>
</template>
