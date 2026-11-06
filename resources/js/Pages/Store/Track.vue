<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Timeline from '../../Components/Timeline.vue';
import { usePolling } from '../../composables/usePolling';
import { fileSize, formatDate, formatShortDate, money, prettyPhone } from '../../lib/format';

const props = defineProps({
    order: { type: Object, required: true },
    justOrdered: { type: Boolean, default: false },
    paymentNumber: { type: String, default: null },
});

const base = computed(() => `/suivi/${props.order.reference}`);
const body = (extra = {}) => ({ token: props.order.token, ...extra });

// La page se met à jour seule tant que la commande est en cours.
usePolling({ only: ['order'], every: 30000, enabled: () => !props.order.isFinal });

const order = computed(() => props.order);
const cancelled = computed(() => order.value.status === 'cancelled');

// Étapes affichées : « Devis » et « Maquette » n'apparaissent que si la commande les utilise.
const steps = computed(() => {
    const status = order.value.status;
    const usesQuote = order.value.confirmedTotal !== null || status === 'quoted';
    const usesProof = order.value.proofs.length > 0 || status === 'proofing';
    const all = [
        ['received', 'Demande reçue'],
        usesQuote && ['quoted', 'Devis à valider'],
        ['confirmed', 'Commande confirmée'],
        usesProof && ['proofing', 'Maquette à valider'],
        ['production', 'En production'],
        ['ready', 'Prête'],
        ['completed', order.value.fulfillment === 'delivery' ? 'Livrée' : 'Retirée'],
    ].filter(Boolean);
    const current = all.findIndex(([k]) => k === status);
    return all.map(([key, label], i) => ({ key, label, state: current === -1 ? 'todo' : i < current ? 'done' : i === current ? 'current' : 'todo' }));
});

const showPayment = computed(() => ['confirmed', 'proofing', 'production', 'ready', 'completed'].includes(order.value.status));

const busy = ref(false);
const post = (url, data = {}, options = {}) => router.post(url, body(data), { preserveScroll: true, onStart: () => (busy.value = true), onFinish: () => (busy.value = false), ...options });

const confirmCancel = ref(false);
const changes = ref({}); // commentaires de modification par maquette
const asking = ref(null); // maquette dont on demande la modification

const approve = (proof) => post(`${base.value}/maquettes/${proof.id}`, { decision: 'approve' });
const requestChanges = (proof) => post(`${base.value}/maquettes/${proof.id}`, { decision: 'changes', comment: changes.value[proof.id] ?? '' }, { onSuccess: () => (asking.value = null) });
</script>

<template>
    <Head :title="`Commande ${order.reference}`" />

    <div class="mx-auto max-w-3xl space-y-6">
        <div v-if="justOrdered" class="rounded-2xl border border-ok/30 bg-ok/5 p-5">
            <div class="flex gap-3">
                <Icon name="circle-check" :size="24" class="mt-0.5 shrink-0 text-ok" />
                <div>
                    <h2 class="font-display text-lg font-extrabold">Demande envoyée, merci !</h2>
                    <p class="mt-1 text-sm text-muted">Nous étudions votre demande puis nous vous contactons au {{ prettyPhone(order.customer.phone) }} pour confirmer le prix et le délai. <b>Gardez cette page</b> (ajoutez-la à vos favoris) : elle vous permet de suivre votre commande et de valider votre devis ou votre maquette.</p>
                </div>
            </div>
        </div>

        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <span class="eyebrow">Suivi de commande</span>
                <h1 class="mt-2 font-display text-3xl font-extrabold">{{ order.reference }}</h1>
                <p class="mt-1 text-sm text-muted">Passée le {{ formatDate(order.createdAt) }}</p>
            </div>
            <StatusBadge :status="order.status" :label="order.statusLabel" />
        </header>

        <section class="card">
            <div class="grid gap-6 sm:grid-cols-[1fr_1.2fr]">
                <Timeline v-if="!cancelled" :steps="steps" />
                <div v-else class="flex items-center gap-3 text-muted"><Icon name="circle-alert" :size="22" /> Commande annulée.</div>
                <div class="self-start rounded-xl bg-paper2 p-4">
                    <h2 class="font-display text-base font-extrabold">{{ order.statusLabel }}</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-muted">{{ order.statusHelp }}</p>
                    <p class="mt-3 flex items-center gap-2 text-sm"><Icon name="phone" :size="15" class="text-gold-700" /> Une question ? <a :href="`tel:+${$page.props.site.phone}`" class="font-bold text-gold-700 hover:underline">Appelez-nous</a></p>
                </div>
            </div>
        </section>

        <!-- Devis à accepter -->
        <section v-if="order.can.acceptQuote" class="card border-gold-500 bg-gold-50">
            <h2 class="flex items-center gap-2 font-display text-xl font-extrabold"><Icon name="receipt" :size="22" class="text-gold-700" /> Votre devis</h2>
            <p class="mt-2 font-display text-3xl font-extrabold">{{ money(order.confirmedTotal) }}</p>
            <p v-if="order.quoteMessage" class="mt-2 rounded-lg bg-white/80 p-3 text-sm">{{ order.quoteMessage }}</p>
            <p class="mt-3 text-sm text-muted">En acceptant, vous confirmez la commande. Un acompte de {{ order.depositPercent }} % ({{ money(order.depositDue) }}) lance la suite.</p>
            <button class="btn-gold mt-4" :disabled="busy" @click="post(`${base}/devis`)"><Icon name="check" :size="18" /> Accepter le devis</button>
        </section>

        <!-- Maquettes -->
        <section v-if="order.proofs.length" class="card space-y-4">
            <h2 class="flex items-center gap-2 font-display text-xl font-extrabold"><Icon name="eye" :size="22" class="text-gold-700" /> Maquette{{ order.proofs.length > 1 ? 's' : '' }}</h2>
            <article v-for="proof in order.proofs" :key="proof.id" class="rounded-xl border border-line p-4">
                <div class="grid gap-4 sm:grid-cols-[180px_1fr]">
                    <a :href="proof.url" target="_blank" rel="noopener" class="halftone grid aspect-[4/3] place-items-center overflow-hidden rounded-lg bg-paper2 text-gold-700">
                        <img v-if="proof.isImage" :src="proof.url" :alt="`Maquette ${proof.name}`" class="h-full w-full object-contain" />
                        <span v-else class="flex flex-col items-center gap-1 text-xs font-bold"><Icon name="file-text" :size="34" />Ouvrir le PDF</span>
                    </a>
                    <div>
                        <b class="block truncate">{{ proof.name }}</b>
                        <span class="text-xs text-muted">{{ formatShortDate(proof.date) }} · {{ fileSize(proof.size) }}</span>
                        <p class="mt-2">
                            <span v-if="proof.status === 'approved'" class="pill inline-flex items-center gap-1.5 rounded-full bg-ok/10 px-2.5 py-1 text-xs font-extrabold text-ok"><Icon name="check" :size="13" :stroke="3" /> Validée</span>
                            <span v-else-if="proof.status === 'changes_requested'" class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-extrabold text-sky-800">Modification demandée</span>
                            <span v-else class="inline-flex rounded-full bg-gold-100 px-2.5 py-1 text-xs font-extrabold text-gold-800">À valider</span>
                        </p>
                        <p v-if="proof.comment" class="mt-2 text-sm text-muted">Votre remarque : « {{ proof.comment }} »</p>

                        <div v-if="order.can.decideProof && proof.status !== 'approved'" class="mt-4">
                            <div v-if="asking === proof.id" class="space-y-2">
                                <textarea v-model="changes[proof.id]" rows="3" class="input" placeholder="Que faut-il modifier ?" aria-label="Modifications souhaitées" />
                                <div class="flex gap-2">
                                    <button class="btn-dark btn-sm" :disabled="busy" @click="requestChanges(proof)">Envoyer</button>
                                    <button class="btn-line btn-sm" @click="asking = null">Annuler</button>
                                </div>
                                <p v-if="$page.props.errors.comment" class="error">{{ $page.props.errors.comment }}</p>
                            </div>
                            <div v-else class="flex flex-wrap gap-2">
                                <button class="btn-gold btn-sm" :disabled="busy" @click="approve(proof)"><Icon name="check" :size="15" /> Valider cette maquette</button>
                                <button class="btn-line btn-sm" @click="asking = proof.id">Demander une modification</button>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <!-- Articles -->
        <section class="card space-y-4">
            <h2 class="flex items-center gap-2 font-display text-xl font-extrabold"><Icon name="package" :size="22" class="text-gold-700" /> Votre commande</h2>
            <ul class="divide-y divide-line">
                <li v-for="item in order.items" :key="item.id" class="py-3 first:pt-0 last:pb-0">
                    <div class="flex justify-between gap-3">
                        <div class="min-w-0">
                            <b>{{ item.quantity }} × {{ item.name }}</b>
                            <ul v-if="item.summary.length" class="mt-1 text-sm text-muted"><li v-for="l in item.summary" :key="l">{{ l }}</li></ul>
                            <details v-if="item.roster.length" class="mt-2 text-sm">
                                <summary class="cursor-pointer font-bold text-gold-700">Voir la liste</summary>
                                <ul class="mt-1 columns-2 text-muted"><li v-for="(r, i) in item.roster" :key="i">{{ r.name || '—' }} · N° {{ r.number }}<template v-if="r.size"> · {{ r.size }}</template></li></ul>
                            </details>
                            <p v-if="item.notes" class="mt-1 text-sm italic text-muted">« {{ item.notes }} »</p>
                        </div>
                        <strong class="shrink-0">{{ item.lineTotal !== null ? money(item.lineTotal) : 'Sur devis' }}</strong>
                    </div>
                </li>
            </ul>
            <dl class="space-y-1.5 border-t border-line pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-muted">{{ order.confirmedTotal !== null ? 'Prix confirmé' : 'Estimation' }}</dt><dd class="font-display text-lg font-extrabold">{{ money(order.total) }}</dd></div>
                <div v-if="order.hasPendingPrice && order.confirmedTotal === null" class="flex justify-between"><dt class="text-muted">Articles sur devis</dt><dd class="font-bold text-gold-700">À confirmer</dd></div>
            </dl>
            <div class="grid gap-3 border-t border-line pt-4 text-sm sm:grid-cols-2">
                <p class="flex gap-2"><Icon :name="order.fulfillment === 'delivery' ? 'truck' : 'store'" :size="17" class="mt-0.5 text-gold-700" /><span><b>{{ order.fulfillment === 'delivery' ? 'Livraison' : 'Retrait à la boutique' }}</b><span v-if="order.address" class="block text-muted">{{ order.address }}<template v-if="order.city">, {{ order.city }}</template></span></span></p>
                <p class="flex gap-2"><Icon name="phone" :size="17" class="mt-0.5 text-gold-700" /><span><b>{{ order.customer.name }}</b><span class="block text-muted">{{ prettyPhone(order.customer.phone) }}</span></span></p>
            </div>
            <ul v-if="order.artworks.length" class="space-y-1.5 border-t border-line pt-4 text-sm">
                <li class="font-bold">Fichiers envoyés</li>
                <li v-for="f in order.artworks" :key="f.id"><a :href="f.url" class="inline-flex items-center gap-2 text-muted hover:text-gold-700"><Icon name="paperclip" :size="14" />{{ f.name }} <span class="text-xs">({{ fileSize(f.size) }})</span></a></li>
            </ul>
        </section>

        <!-- Paiement -->
        <section v-if="showPayment" class="card space-y-3">
            <h2 class="flex items-center gap-2 font-display text-xl font-extrabold"><Icon name="wallet" :size="22" class="text-gold-700" /> Paiement</h2>
            <dl class="grid grid-cols-3 gap-3 text-center text-sm">
                <div class="rounded-xl bg-paper2 p-3"><dt class="text-xs text-muted">Total</dt><dd class="font-display font-extrabold">{{ money(order.total) }}</dd></div>
                <div class="rounded-xl bg-paper2 p-3"><dt class="text-xs text-muted">Déjà réglé</dt><dd class="font-display font-extrabold text-ok">{{ money(order.paid) }}</dd></div>
                <div class="rounded-xl bg-gold-100 p-3"><dt class="text-xs text-muted">Reste à payer</dt><dd class="font-display font-extrabold">{{ money(order.balance) }}</dd></div>
            </dl>
            <p v-if="order.paid < order.depositDue && order.balance > 0" class="text-sm text-muted">Acompte demandé : <b>{{ money(order.depositDue) }}</b> ({{ order.depositPercent }} %).
                <template v-if="paymentNumber"> Par Wave ou Orange Money au <b>{{ paymentNumber }}</b>, ou en espèces à la boutique.</template>
                <template v-else> Nous vous indiquons comment le régler (Wave, Orange Money ou espèces).</template>
            </p>
            <ul v-if="order.payments.length" class="space-y-1 text-sm text-muted"><li v-for="p in order.payments" :key="p.id">{{ formatShortDate(p.date) }} — {{ money(p.amount) }} ({{ p.method }})</li></ul>
        </section>

        <!-- Historique -->
        <section class="card">
            <h2 class="mb-3 flex items-center gap-2 font-display text-xl font-extrabold"><Icon name="clock" :size="22" class="text-gold-700" /> Historique</h2>
            <ol class="space-y-2 text-sm">
                <li v-for="(e, i) in [...order.events].reverse()" :key="i" class="flex gap-3"><time class="w-28 shrink-0 text-xs text-muted">{{ formatShortDate(e.date) }}</time><span>{{ e.message }}</span></li>
            </ol>
        </section>

        <div v-if="order.can.cancel" class="text-center">
            <button v-if="!confirmCancel" class="text-sm font-bold text-muted underline hover:text-danger" @click="confirmCancel = true">Annuler ma commande</button>
            <div v-else class="inline-flex flex-col items-center gap-2">
                <p class="text-sm text-muted">Annuler définitivement cette commande ?</p>
                <div class="flex gap-2"><button class="btn-danger btn-sm" :disabled="busy" @click="post(`${base}/annuler`)">Oui, annuler</button><button class="btn-line btn-sm" @click="confirmCancel = false">Non, garder</button></div>
            </div>
        </div>
    </div>
</template>
