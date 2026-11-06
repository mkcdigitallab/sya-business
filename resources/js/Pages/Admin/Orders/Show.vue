<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import Icon from '../../../Components/Icon.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { fileSize, formatDate, formatShortDate, money, prettyPhone } from '../../../lib/format';

const props = defineProps({ order: { type: Object, required: true }, paymentMethods: { type: Array, required: true } });

const errors = computed(() => usePage().props.errors);
const o = computed(() => props.order);
const needsPrice = computed(() => props.order.hasPendingPrice && props.order.confirmedTotal === null);

// ── Changement de statut ──
const force = ref(false);
const change = (status) => router.patch(`/admin/commandes/${o.value.reference}/statut`, { status, force: force.value }, { preserveScroll: true });

// ── Devis ──
const quote = useForm({ confirmed_total: props.order.confirmedTotal ?? props.order.estimatedTotal ?? '', quote_message: props.order.quoteMessage ?? '' });
const sendQuote = () => quote.post(`/admin/commandes/${o.value.reference}/devis`, { preserveScroll: true });

// ── Maquette ──
const proof = useForm({ proof: [] });
const sendProof = (e) => { proof.proof = [...e.target.files]; proof.post(`/admin/commandes/${o.value.reference}/maquettes`, { forceFormData: true, preserveScroll: true, onFinish: () => (e.target.value = '') }); };

// ── Paiement ──
const payment = useForm({ amount: props.order.balance > 0 ? Math.min(props.order.balance, props.order.depositDue) : '', method: 'wave', note: '' });
const addPayment = () => payment.post(`/admin/commandes/${o.value.reference}/paiements`, { preserveScroll: true, onSuccess: () => payment.reset('note') });

// ── Notes internes ──
const notes = useForm({ internal_notes: props.order.internalNotes ?? '' });
const saveNotes = () => notes.patch(`/admin/commandes/${o.value.reference}/notes`, { preserveScroll: true });

const proofTone = { approved: 'text-ok', changes_requested: 'text-sky-700', pending: 'text-gold-700' };
const proofLabel = { approved: 'Validée par le client', changes_requested: 'Modification demandée', pending: 'En attente du client' };
</script>

<template>
    <Head :title="order.reference" />

    <div class="space-y-6">
        <div>
            <Link href="/admin/commandes" class="mb-2 inline-flex items-center gap-1.5 text-sm font-bold text-muted hover:text-ink"><Icon name="arrow-left" :size="15" /> Commandes</Link>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h1 class="font-mono text-3xl font-extrabold tracking-tight">{{ order.reference }}</h1><p class="text-sm text-muted">Passée le {{ formatDate(order.createdAt) }}</p></div>
                <StatusBadge :status="order.status" :label="order.statusLabel" />
            </div>
        </div>

        <div class="grid items-start gap-6 lg:grid-cols-[1.5fr_1fr]">
            <div class="space-y-6">
                <!-- Articles -->
                <section class="card-flat space-y-4">
                    <h2 class="font-display text-lg font-extrabold">Articles</h2>
                    <article v-for="item in order.items" :key="item.id" class="rounded-xl border border-line p-4">
                        <div class="flex justify-between gap-3">
                            <b>{{ item.quantity }} × {{ item.name }}</b>
                            <strong>{{ item.lineTotal !== null ? money(item.lineTotal) : 'Sur devis' }}</strong>
                        </div>
                        <ul v-if="item.summary.length" class="mt-1 text-sm text-muted"><li v-for="l in item.summary" :key="l">· {{ l }}</li></ul>
                        <p v-if="item.notes" class="mt-2 rounded-lg bg-paper2 px-3 py-2 text-sm">« {{ item.notes }} »</p>
                        <table v-if="item.roster.length" class="mt-3 w-full text-sm">
                            <thead class="text-left text-xs uppercase text-muted"><tr><th class="py-1">#</th><th>Nom</th><th>N°</th><th>Taille</th></tr></thead>
                            <tbody><tr v-for="(r, i) in item.roster" :key="i" class="border-t border-line/60"><td class="py-1 text-muted">{{ i + 1 }}</td><td class="font-bold">{{ r.name || '—' }}</td><td>{{ r.number }}</td><td>{{ r.size || '—' }}</td></tr></tbody>
                        </table>
                    </article>
                    <div v-if="order.notes" class="rounded-xl bg-gold-50 p-4 text-sm"><b class="mb-1 block">Message du client</b>{{ order.notes }}</div>
                    <div v-if="order.artworks.length"><b class="mb-2 block text-sm">Fichiers du client</b>
                        <ul class="grid gap-2 sm:grid-cols-2">
                            <li v-for="f in order.artworks" :key="f.id"><a :href="f.url" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-lg border border-line p-2.5 text-sm transition hover:border-gold-500">
                                <img v-if="f.isImage" :src="f.url" alt="" class="h-10 w-10 rounded object-cover" /><Icon v-else name="file-text" :size="22" class="text-gold-700" />
                                <span class="min-w-0"><b class="block truncate">{{ f.name }}</b><span class="text-xs text-muted">{{ fileSize(f.size) }}</span></span></a></li>
                        </ul>
                    </div>
                </section>

                <!-- Maquettes -->
                <section class="card-flat space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-display text-lg font-extrabold">Maquettes</h2>
                        <label v-if="order.canAddProof" class="btn-gold btn-sm cursor-pointer"><Icon name="upload" :size="15" /> Envoyer une maquette<input type="file" class="hidden" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple @change="sendProof" /></label>
                    </div>
                    <p v-if="!order.canAddProof && !order.proofs.length" class="text-sm text-muted">Les maquettes s’envoient une fois la commande confirmée.</p>
                    <p v-if="errors.proof" class="error">{{ errors.proof }}</p>
                    <ul class="space-y-3">
                        <li v-for="p in order.proofs" :key="p.id" class="flex gap-3 rounded-xl border border-line p-3">
                            <a :href="p.url" target="_blank" rel="noopener" class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-lg bg-paper2 text-gold-700"><img v-if="p.isImage" :src="p.url" alt="" class="h-full w-full object-cover" /><Icon v-else name="file-text" :size="26" /></a>
                            <div class="min-w-0"><b class="block truncate">{{ p.name }}</b><span class="text-sm font-bold" :class="proofTone[p.status]">{{ proofLabel[p.status] }}</span><p v-if="p.comment" class="mt-1 text-sm text-muted">« {{ p.comment }} »</p></div>
                        </li>
                    </ul>
                </section>

                <!-- Historique -->
                <section class="card-flat">
                    <h2 class="mb-3 font-display text-lg font-extrabold">Historique</h2>
                    <ol class="space-y-2 text-sm">
                        <li v-for="(e, i) in [...order.events].reverse()" :key="i" class="flex gap-3"><time class="w-28 shrink-0 text-xs text-muted">{{ formatShortDate(e.date) }}</time><span><b class="capitalize">{{ e.actor }}</b> · {{ e.message }}</span></li>
                    </ol>
                </section>
            </div>

            <aside class="space-y-6">
                <!-- Statut -->
                <section class="card-flat space-y-3">
                    <h2 class="font-display text-lg font-extrabold">Étape suivante</h2>
                    <p v-if="!order.nextStatuses.length && !order.canSendQuote" class="text-sm text-muted">Cette commande est terminée.</p>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="s in order.nextStatuses" :key="s.value" class="btn-sm" :class="s.value === 'cancelled' ? 'btn-danger' : 'btn-dark'" :disabled="s.value === 'confirmed' && needsPrice" @click="change(s.value)">{{ s.label }}</button>
                    </div>
                    <p v-if="needsPrice" class="rounded-lg bg-gold-50 p-2.5 text-xs text-gold-900">Cette commande contient un article sur devis : fixez d’abord le prix ci-dessous.</p>
                    <label v-if="order.status === 'proofing' || order.status === 'confirmed'" class="flex items-start gap-2 text-xs text-muted"><input v-model="force" type="checkbox" class="mt-0.5 rounded border-line text-gold-600 focus:ring-gold-500" /> Accord reçu autrement (lancer sans validation de maquette)</label>
                </section>

                <!-- Devis -->
                <section v-if="order.canSendQuote" class="card-flat space-y-3">
                    <h2 class="font-display text-lg font-extrabold">Prix et devis</h2>
                    <p class="text-xs text-muted">Estimation du client : {{ money(order.estimatedTotal) }}<span v-if="order.hasPendingPrice"> + articles sur devis</span>. Fixez le prix définitif : le client est invité à l’accepter.</p>
                    <div><label class="label" for="total">Prix définitif (FCFA)</label><input id="total" v-model="quote.confirmed_total" type="number" min="1" class="input" :class="{ 'input-error': quote.errors.confirmed_total }" /><p v-if="quote.errors.confirmed_total" class="error">{{ quote.errors.confirmed_total }}</p></div>
                    <div><label class="label" for="qm">Message au client <span class="font-normal text-muted">(facultatif)</span></label><textarea id="qm" v-model="quote.quote_message" rows="2" class="input" placeholder="Délai, livraison incluse…" /></div>
                    <button class="btn-gold btn-block btn-sm" :disabled="quote.processing" @click="sendQuote"><Icon name="receipt" :size="15" /> Envoyer le devis</button>
                </section>

                <!-- Paiements -->
                <section class="card-flat space-y-3">
                    <h2 class="font-display text-lg font-extrabold">Paiements</h2>
                    <dl class="grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="rounded-lg bg-paper2 p-2"><dt class="text-muted">Total</dt><dd class="font-display text-sm font-extrabold">{{ money(order.total) }}</dd></div>
                        <div class="rounded-lg bg-paper2 p-2"><dt class="text-muted">Réglé</dt><dd class="font-display text-sm font-extrabold text-ok">{{ money(order.paid) }}</dd></div>
                        <div class="rounded-lg bg-gold-100 p-2"><dt class="text-muted">Reste</dt><dd class="font-display text-sm font-extrabold">{{ money(order.balance) }}</dd></div>
                    </dl>
                    <p class="text-xs text-muted">Acompte demandé : {{ money(order.depositDue) }} ({{ order.depositPercent }} %)</p>
                    <ul v-if="order.payments.length" class="space-y-1 text-sm"><li v-for="p in order.payments" :key="p.id" class="flex justify-between"><span class="text-muted">{{ formatShortDate(p.date) }} · {{ p.method }}</span><b>{{ money(p.amount) }}</b></li></ul>
                    <form v-if="order.balance > 0 && order.status !== 'cancelled'" class="space-y-2 border-t border-line pt-3" @submit.prevent="addPayment">
                        <div class="grid grid-cols-2 gap-2">
                            <input v-model="payment.amount" type="number" min="1" class="input !py-2" :class="{ 'input-error': payment.errors.amount }" placeholder="Montant" aria-label="Montant reçu" />
                            <select v-model="payment.method" class="input !py-2" aria-label="Moyen de paiement"><option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option></select>
                        </div>
                        <p v-if="payment.errors.amount" class="error">{{ payment.errors.amount }}</p>
                        <input v-model="payment.note" class="input !py-2" placeholder="Note (facultatif)" aria-label="Note du paiement" />
                        <button class="btn-dark btn-block btn-sm" :disabled="payment.processing"><Icon name="banknote" :size="15" /> Enregistrer le paiement</button>
                    </form>
                </section>

                <!-- Client -->
                <section class="card-flat space-y-2 text-sm">
                    <h2 class="font-display text-lg font-extrabold">Client</h2>
                    <p class="font-bold">{{ order.customer.name }}</p>
                    <a :href="`tel:${order.customer.phone}`" class="flex items-center gap-2 text-gold-700 hover:underline"><Icon name="phone" :size="15" />{{ prettyPhone(order.customer.phone) }}</a>
                    <a v-if="order.customer.email" :href="`mailto:${order.customer.email}`" class="flex items-center gap-2 text-gold-700 hover:underline"><Icon name="mail" :size="15" />{{ order.customer.email }}</a>
                    <p class="flex items-start gap-2 text-muted"><Icon :name="order.fulfillment === 'delivery' ? 'truck' : 'store'" :size="15" class="mt-0.5" /><span>{{ order.fulfillment === 'delivery' ? `Livraison — ${order.address}, ${order.city}` : 'Retrait à la boutique' }}</span></p>
                    <p v-if="order.paymentPreference" class="text-xs text-muted">Moyen d’acompte préféré : {{ order.paymentPreference }}</p>
                </section>

                <section class="card-flat space-y-2">
                    <h2 class="font-display text-lg font-extrabold">Notes internes</h2>
                    <textarea v-model="notes.internal_notes" rows="3" class="input" placeholder="Visibles uniquement par l’atelier" aria-label="Notes internes" />
                    <button class="btn-line btn-sm" :disabled="notes.processing || !notes.isDirty" @click="saveNotes">Enregistrer</button>
                </section>
            </aside>
        </div>
    </div>
</template>
