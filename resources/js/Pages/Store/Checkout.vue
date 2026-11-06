<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import FileDropzone from '../../Components/FileDropzone.vue';
import Icon from '../../Components/Icon.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { money } from '../../lib/format';

const props = defineProps({
    cart: { type: Object, required: true },
    clientReference: { type: String, required: true },
    paymentMethods: { type: Array, required: true },
    paymentNumber: { type: String, default: null },
    limits: { type: Object, required: true },
});

const site = computed(() => usePage().props.site);

const form = useForm({
    client_reference: props.clientReference,
    customer_name: '',
    phone: '',
    email: '',
    fulfillment: 'pickup',
    address: '',
    city: '',
    notes: '',
    payment_preference: 'wave',
    files: [],
    accept_terms: false,
});

const delivery = computed(() => form.fulfillment === 'delivery');
const fileError = computed(() => Object.entries(form.errors).find(([k]) => k === 'files' || k.startsWith('files.'))?.[1] ?? '');
const submit = () => form.post('/commande', { forceFormData: true, preserveScroll: true });
</script>

<template>
    <Head title="Envoyer ma demande" />

    <div class="space-y-8">
        <PageHeader eyebrow="Dernière étape" title="Envoyer ma demande" :back="{ href: '/panier', label: 'Retour au panier' }" />

        <form class="grid items-start gap-8 lg:grid-cols-[1fr_340px]" novalidate @submit.prevent="submit">
            <div class="space-y-6">
                <section class="card space-y-4">
                    <h2 class="flex items-center gap-2 font-display text-lg font-extrabold"><Icon name="user" :size="20" class="text-gold-700" /> Vos coordonnées</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="label" for="name">Nom et prénom</label>
                            <input id="name" v-model="form.customer_name" class="input" :class="{ 'input-error': form.errors.customer_name }" autocomplete="name" />
                            <p v-if="form.errors.customer_name" class="error">{{ form.errors.customer_name }}</p>
                        </div>
                        <div>
                            <label class="label" for="phone">Téléphone</label>
                            <input id="phone" v-model="form.phone" type="tel" class="input" :class="{ 'input-error': form.errors.phone }" placeholder="+221 77 123 45 67" autocomplete="tel" />
                            <p v-if="form.errors.phone" class="error">{{ form.errors.phone }}</p>
                        </div>
                        <div>
                            <label class="label" for="email">Email <span class="font-normal text-muted">(facultatif)</span></label>
                            <input id="email" v-model="form.email" type="email" class="input" :class="{ 'input-error': form.errors.email }" autocomplete="email" />
                            <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
                        </div>
                    </div>
                </section>

                <section class="card space-y-4">
                    <h2 class="flex items-center gap-2 font-display text-lg font-extrabold"><Icon name="truck" :size="20" class="text-gold-700" /> Retrait ou livraison</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <button type="button" class="chip !h-auto flex-col items-start gap-1 !px-4 !py-3 text-left" :class="{ 'chip-on': !delivery }" @click="form.fulfillment = 'pickup'"><b class="flex items-center gap-2"><Icon name="store" :size="18" /> Je viens chercher</b><small class="font-normal text-muted">Retrait à la boutique</small></button>
                        <button type="button" class="chip !h-auto flex-col items-start gap-1 !px-4 !py-3 text-left" :class="{ 'chip-on': delivery }" @click="form.fulfillment = 'delivery'"><b class="flex items-center gap-2"><Icon name="truck" :size="18" /> Livraison</b><small class="font-normal text-muted">Frais confirmés avec le devis</small></button>
                    </div>
                    <div v-if="delivery" class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="city">Quartier / ville</label>
                            <input id="city" v-model="form.city" class="input" :class="{ 'input-error': form.errors.city }" placeholder="Ex. Guédiawaye" />
                            <p v-if="form.errors.city" class="error">{{ form.errors.city }}</p>
                        </div>
                        <div>
                            <label class="label" for="address">Adresse et repère</label>
                            <input id="address" v-model="form.address" class="input" :class="{ 'input-error': form.errors.address }" placeholder="Rue, près de…" />
                            <p v-if="form.errors.address" class="error">{{ form.errors.address }}</p>
                        </div>
                    </div>
                </section>

                <section class="card space-y-4">
                    <h2 class="flex items-center gap-2 font-display text-lg font-extrabold"><Icon name="paperclip" :size="20" class="text-gold-700" /> Vos fichiers et précisions</h2>
                    <FileDropzone v-model="form.files" :max="limits.files" :size-mb="limits.sizeMb" :extensions="limits.extensions" :error="fileError" />
                    <p v-if="cart.needsArtwork" class="hint">Certains de vos articles se personnalisent avec un logo ou un visuel : envoyez-le ici. Sans fichier, nous vous contacterons.</p>
                    <div>
                        <label class="label" for="notes">Message <span class="font-normal text-muted">(facultatif)</span></label>
                        <textarea id="notes" v-model="form.notes" rows="3" class="input" placeholder="Date souhaitée, précisions…" />
                    </div>
                </section>

                <section class="card space-y-4">
                    <h2 class="flex items-center gap-2 font-display text-lg font-extrabold"><Icon name="wallet" :size="20" class="text-gold-700" /> Paiement de l’acompte</h2>
                    <p class="text-sm text-muted">Rien n’est à payer maintenant. Quand le prix est confirmé, un acompte de {{ site.depositPercent }} % lance la commande. Choisissez le moyen qui vous convient :</p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <button v-for="m in paymentMethods" :key="m.value" type="button" class="chip !h-auto justify-start gap-2 !px-4 !py-3" :class="{ 'chip-on': form.payment_preference === m.value }" @click="form.payment_preference = m.value">
                            <Icon :name="m.value === 'cash' ? 'banknote' : 'phone'" :size="18" /> {{ m.label }}
                        </button>
                    </div>
                    <p v-if="paymentNumber" class="hint">Acompte par Wave ou Orange Money : {{ paymentNumber }}. Nous vous le rappelons au moment de la confirmation.</p>
                </section>

                <label class="flex cursor-pointer items-start gap-3 text-sm">
                    <input v-model="form.accept_terms" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-line text-gold-600 focus:ring-gold-500" />
                    <span>J’ai lu <Link href="/informations" class="font-bold text-gold-700 underline" target="_blank">comment ça marche</Link> et je comprends que le prix définitif est confirmé avant la production. Je confirme avoir le droit d’utiliser les visuels envoyés.</span>
                </label>
                <p v-if="form.errors.accept_terms" class="error -mt-3">{{ form.errors.accept_terms }}</p>
            </div>

            <aside class="card sticky top-24 space-y-4">
                <h2 class="font-display text-xl font-extrabold">Votre demande</h2>
                <ul class="space-y-3 text-sm">
                    <li v-for="i in cart.items" :key="i.id" class="flex justify-between gap-3 border-b border-line/70 pb-3 last:border-0 last:pb-0">
                        <span class="min-w-0"><b class="block truncate">{{ i.name }}</b><span class="text-xs text-muted">{{ i.quantity }} {{ i.unitLabel }}</span></span>
                        <strong class="shrink-0">{{ i.lineTotal !== null ? money(i.lineTotal) : 'Sur devis' }}</strong>
                    </li>
                </ul>
                <div class="flex items-end justify-between border-t border-line pt-4"><span class="font-bold">Estimation</span><strong class="font-display text-2xl font-extrabold">{{ money(cart.subtotal) }}</strong></div>
                <p v-if="cart.hasPending" class="text-xs text-muted">Hors articles sur devis, dont le prix vous sera communiqué.</p>
                <p v-if="form.errors.general || form.errors.items" class="error">{{ form.errors.general || form.errors.items }}</p>
                <button class="btn-gold btn-block" :disabled="form.processing"><Icon name="check" :size="18" /> {{ form.processing ? 'Envoi…' : 'Envoyer ma demande' }}</button>
            </aside>
        </form>
    </div>
</template>
