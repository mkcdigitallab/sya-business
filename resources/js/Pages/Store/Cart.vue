<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import PageHeader from '../../Components/PageHeader.vue';
import ProductImage from '../../Components/ProductImage.vue';
import QuantityStepper from '../../Components/QuantityStepper.vue';
import { money, plural } from '../../lib/format';

const props = defineProps({ cart: { type: Object, required: true } });

const deposit = computed(() => usePage().props.site.depositPercent);
const empty = computed(() => props.cart.items.length === 0);

const setQuantity = (item, quantity) => router.patch(`/panier/${item.id}`, { quantity }, { preserveScroll: true });
const remove = (item) => router.delete(`/panier/${item.id}`, { preserveScroll: true });
</script>

<template>
    <Head title="Mon panier" />

    <div class="space-y-8">
        <PageHeader eyebrow="Panier" title="Ma commande" :back="{ href: '/boutique', label: 'Continuer mes achats' }" />

        <div v-if="empty" class="card flex flex-col items-center gap-3 py-16 text-center">
            <Icon name="shopping-bag" :size="38" class="text-muted" :stroke="1.5" />
            <h2 class="text-2xl font-extrabold">Votre panier est vide</h2>
            <p class="max-w-sm text-muted">Choisissez un produit ou un service : tailles, noms, dimensions… tout se règle sur sa fiche.</p>
            <div class="flex gap-2"><Link href="/boutique" class="btn-gold">Voir la boutique</Link><Link href="/devis" class="btn-line">Demander un devis</Link></div>
        </div>

        <div v-else class="grid items-start gap-8 lg:grid-cols-[1fr_340px]">
            <ul class="space-y-4">
                <li v-for="item in cart.items" :key="item.id" class="card" :class="{ 'border-danger/40 bg-danger/5': !item.available }">
                    <div class="flex gap-4">
                        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl sm:h-24 sm:w-24">
                            <ProductImage :src="item.image" :name="item.name" :family="item.family" :icon-size="32" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <Link v-if="item.slug" :href="`/produit/${item.slug}`" class="font-display text-lg font-extrabold leading-snug hover:text-gold-700">{{ item.name }}</Link>
                                    <b v-else class="font-display text-lg">{{ item.name }}</b>
                                    <p v-if="!item.available" class="mt-1 text-sm font-bold text-danger">Cet article n’est plus disponible ou sa personnalisation a changé : retirez-le.</p>
                                </div>
                                <button class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-muted transition hover:bg-danger/10 hover:text-danger" :aria-label="`Retirer ${item.name}`" @click="remove(item)"><Icon name="trash" :size="18" /></button>
                            </div>

                            <ul v-if="item.summary.length" class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">
                                <li v-for="line in item.summary" :key="line" class="flex items-center gap-1.5"><Icon name="check" :size="13" class="text-gold-700" />{{ line }}</li>
                            </ul>
                            <p v-if="item.notes" class="mt-2 rounded-lg bg-paper2 px-3 py-2 text-sm text-muted">« {{ item.notes }} »</p>

                            <details v-if="item.roster.length" class="mt-3 rounded-lg border border-line text-sm">
                                <summary class="cursor-pointer select-none px-3 py-2 font-bold">Voir la liste ({{ plural(item.roster.length, 'ligne', 'lignes') }})</summary>
                                <table class="w-full border-t border-line text-left">
                                    <tbody>
                                        <tr v-for="(r, i) in item.roster" :key="i" class="border-b border-line/60 last:border-0">
                                            <td class="px-3 py-1.5 text-muted">{{ i + 1 }}</td>
                                            <td class="px-3 py-1.5 font-bold">{{ r.name || '—' }}</td>
                                            <td class="px-3 py-1.5">N° {{ r.number }}</td>
                                            <td v-if="r.size" class="px-3 py-1.5 text-muted">{{ r.size }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </details>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                                <QuantityStepper v-if="item.editableQuantity && item.available" :model-value="item.quantity" :min="item.minQuantity" label="quantité" @update:model-value="setQuantity(item, $event)" />
                                <span v-else-if="item.available" class="text-sm font-bold text-muted">{{ plural(item.quantity, item.unitLabel, item.unitLabel) }}</span>
                                <span v-else />
                                <div class="text-right">
                                    <strong v-if="item.lineTotal !== null" class="block font-display text-lg font-extrabold">{{ money(item.lineTotal) }}</strong>
                                    <strong v-else-if="item.available" class="block font-display text-sm font-extrabold text-gold-700">Prix sur devis</strong>
                                    <small v-if="item.unitPrice !== null && item.quantity > 1" class="text-xs text-muted">{{ money(item.unitPrice) }} l’unité</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>

            <aside class="card sticky top-24 space-y-4">
                <h2 class="font-display text-xl font-extrabold">Récapitulatif</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">{{ plural(cart.items.length, 'article', 'articles') }}</dt><dd class="font-bold">{{ money(cart.subtotal) }}</dd></div>
                    <div v-if="cart.hasPending" class="flex justify-between"><dt class="text-muted">Articles sur devis</dt><dd class="font-bold text-gold-700">À confirmer</dd></div>
                </dl>
                <div class="flex items-end justify-between border-t border-line pt-4">
                    <span class="font-bold">Estimation</span>
                    <strong class="font-display text-2xl font-extrabold">{{ money(cart.subtotal) }}</strong>
                </div>
                <p class="rounded-lg bg-gold-50 p-3 text-xs leading-relaxed text-gold-900">
                    Aucun paiement maintenant. Nous confirmons le prix définitif avant de commencer ; un acompte de {{ deposit }} % est demandé à la confirmation.
                </p>
                <Link href="/commande" class="btn-gold btn-block" :class="{ 'pointer-events-none opacity-50': cart.hasUnavailable }" :aria-disabled="cart.hasUnavailable">Envoyer ma demande <Icon name="arrow-right" :size="18" /></Link>
                <Link href="/boutique" class="btn-line btn-block">Ajouter un autre article</Link>
            </aside>
        </div>
    </div>
</template>
