<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import ProductCard from '../../Components/ProductCard.vue';
import ProductImage from '../../Components/ProductImage.vue';
import QuantityStepper from '../../Components/QuantityStepper.vue';
import RosterEditor from '../../Components/RosterEditor.vue';
import { estimate } from '../../lib/pricing';
import { money } from '../../lib/format';
import { priceLabel } from '../../lib/product';

const props = defineProps({ product: { type: Object, required: true }, related: { type: Array, default: () => [] } });

const o = props.product.options;
const sizeInRoster = !!o.roster && o.roster.fields.includes('size');
const defaultSize = (list) => list[Math.floor(list.length / 2)] ?? '';
const blankLine = () => ({ name: '', number: '', size: defaultSize(o.sizes) });

// Toute la personnalisation vit dans ce formulaire ; il est envoyé tel quel à POST /panier.
const form = useForm({
    product: props.product.slug,
    quantity: props.product.minQuantity,
    size: o.sizes.length && !sizeInRoster ? defaultSize(o.sizes) : '',
    choices: Object.fromEntries(o.choices.map((c) => [c.key, c.values[0].value])),
    extras: o.extras.filter((e) => e.locked || e.default).map((e) => e.key),
    width_cm: '',
    height_cm: '',
    roster: o.roster ? [blankLine()] : [],
    notes: '',
});

const preview = computed(() => estimate(props.product, form));
const isQuote = computed(() => props.product.mode === 'quote');
const quantityLabel = computed(() => (props.product.mode === 'pack' ? 'Nombre de packs' : 'Quantité'));

function toggleExtra(extra) {
    if (extra.locked) return;
    form.extras = form.extras.includes(extra.key) ? form.extras.filter((k) => k !== extra.key) : [...form.extras, extra.key];
}

const submit = () => form.post('/panier', { preserveScroll: true });
</script>

<template>
    <Head :title="product.name" />

    <div class="space-y-14">
        <div>
            <nav class="mb-5 flex flex-wrap items-center gap-1.5 text-sm text-muted" aria-label="Fil d’Ariane">
                <Link href="/boutique" class="hover:text-ink">Boutique</Link><Icon name="chevron-right" :size="14" />
                <Link :href="`/boutique?famille=${product.family}`" class="hover:text-ink">{{ product.familyLabel }}</Link><Icon name="chevron-right" :size="14" />
                <span class="font-bold text-ink">{{ product.name }}</span>
            </nav>

            <div class="grid gap-8 lg:grid-cols-[1fr_1.05fr]">
                <!-- Présentation -->
                <div class="lg:sticky lg:top-24 lg:self-start">
                    <div class="crop mx-1">
                        <div class="aspect-[4/3] overflow-hidden rounded-2xl border border-line">
                            <ProductImage :src="product.image" :name="product.name" :family="product.family" :icon-size="84" />
                        </div>
                    </div>
                    <div class="mt-6">
                        <span class="eyebrow">{{ product.familyLabel }}</span>
                        <h1 class="mt-2 text-3xl font-extrabold sm:text-4xl">{{ product.name }}</h1>
                        <p class="mt-2 font-display text-xl font-extrabold text-gold-700">{{ priceLabel(product) }}</p>
                        <p class="mt-4 leading-relaxed text-muted">{{ product.description }}</p>
                        <ul class="mt-5 space-y-2 text-sm">
                            <li class="flex gap-2.5"><Icon name="badge-check" :size="18" class="mt-0.5 text-gold-700" />Prix définitif confirmé avant la production</li>
                            <li v-if="o.needsArtwork || o.roster" class="flex gap-2.5"><Icon name="eye" :size="18" class="mt-0.5 text-gold-700" />Maquette à valider avant l’impression</li>
                            <li v-if="product.leadTimeDays" class="flex gap-2.5"><Icon name="clock" :size="18" class="mt-0.5 text-gold-700" />Délai indicatif : {{ product.leadTimeDays }} jours</li>
                        </ul>
                    </div>
                </div>

                <!-- Configurateur -->
                <form class="card space-y-6" novalidate @submit.prevent="submit">
                    <h2 class="font-display text-xl font-extrabold">{{ isQuote ? 'Décrivez votre projet' : 'Personnalisez votre commande' }}</h2>

                    <fieldset v-if="o.roster">
                        <legend class="label">Votre liste <span class="font-normal text-muted">— une ligne par {{ o.roster.fields.includes('size') ? 'maillot' : 'pièce' }}</span></legend>
                        <RosterEditor v-model="form.roster" :fields="o.roster.fields" :required="o.roster.required" :sizes="o.sizes" :errors="form.errors" />
                    </fieldset>

                    <fieldset v-if="o.sizes.length && !sizeInRoster">
                        <legend class="label">{{ o.sizeLabel }}</legend>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="s in o.sizes" :key="s" type="button" class="chip" :class="{ 'chip-on': form.size === s }" :aria-pressed="form.size === s" @click="form.size = s">{{ s }}</button>
                        </div>
                        <p v-if="form.errors.size" class="error">{{ form.errors.size }}</p>
                    </fieldset>

                    <fieldset v-for="c in o.choices" :key="c.key">
                        <legend class="label">{{ c.label }}</legend>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="v in c.values" :key="v.value" type="button" class="chip" :class="{ 'chip-on': form.choices[c.key] === v.value }" :aria-pressed="form.choices[c.key] === v.value" @click="form.choices[c.key] = v.value">
                                {{ v.label }}<span v-if="v.price" class="ml-1.5 text-xs text-muted">+{{ money(v.price) }}</span>
                            </button>
                        </div>
                        <p v-if="form.errors[`choices.${c.key}`]" class="error">{{ form.errors[`choices.${c.key}`] }}</p>
                    </fieldset>

                    <fieldset v-if="product.mode === 'area'">
                        <legend class="label">Dimensions</legend>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="relative"><input v-model="form.width_cm" type="number" inputmode="numeric" min="1" class="input pr-12" :class="{ 'input-error': form.errors.width_cm }" placeholder="Largeur" aria-label="Largeur en centimètres" /><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-muted">cm</span></div>
                                <p v-if="form.errors.width_cm" class="error">{{ form.errors.width_cm }}</p>
                            </div>
                            <div>
                                <div class="relative"><input v-model="form.height_cm" type="number" inputmode="numeric" min="1" class="input pr-12" :class="{ 'input-error': form.errors.height_cm }" placeholder="Hauteur" aria-label="Hauteur en centimètres" /><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-muted">cm</span></div>
                                <p v-if="form.errors.height_cm" class="error">{{ form.errors.height_cm }}</p>
                            </div>
                        </div>
                        <p class="hint">Entre {{ o.dimensions?.min_cm ?? 10 }} et {{ o.dimensions?.max_cm ?? 1000 }} cm. Le prix est calculé au mètre carré.</p>
                    </fieldset>

                    <fieldset v-if="o.extras.length">
                        <legend class="label">Options</legend>
                        <div class="space-y-2">
                            <label v-for="e in o.extras" :key="e.key" class="flex cursor-pointer items-center gap-3 rounded-xl border border-line px-4 py-3 transition hover:border-gold-500" :class="{ 'border-gold-600 bg-gold-50': form.extras.includes(e.key) }">
                                <input type="checkbox" class="h-4 w-4 rounded border-line text-gold-600 focus:ring-gold-500" :checked="form.extras.includes(e.key)" :disabled="e.locked" @change="toggleExtra(e)" />
                                <span class="flex-1 text-sm font-semibold">{{ e.label }}<small v-if="e.locked" class="ml-2 font-normal text-muted">inclus</small></span>
                                <span v-if="e.price" class="text-sm font-bold text-gold-700">+{{ money(e.price) }}</span>
                            </label>
                        </div>
                    </fieldset>

                    <div v-if="!o.roster">
                        <label class="label" for="qty">{{ quantityLabel }}<span v-if="product.mode === 'pack' && o.packSize" class="font-normal text-muted"> — 1 pack = {{ o.packSize }} exemplaires</span></label>
                        <QuantityStepper v-model="form.quantity" :min="product.minQuantity" :label="quantityLabel.toLowerCase()" />
                        <p v-if="form.errors.quantity" class="error">{{ form.errors.quantity }}</p>
                    </div>

                    <div>
                        <label class="label" for="notes">Précisions <span class="font-normal text-muted">(facultatif)</span></label>
                        <textarea id="notes" v-model="form.notes" rows="3" maxlength="500" class="input" :class="{ 'input-error': form.errors.notes }" :placeholder="o.noteHint ? `Ex. ${o.noteHint}` : 'Couleurs, emplacement, délai souhaité…'" />
                        <p v-if="form.errors.notes" class="error">{{ form.errors.notes }}</p>
                        <p v-if="o.needsArtwork" class="hint flex items-center gap-1.5"><Icon name="paperclip" :size="13" /> Vous pourrez envoyer votre logo ou votre visuel à l’étape suivante.</p>
                    </div>

                    <!-- Estimation -->
                    <div class="rounded-xl bg-ink p-4 text-paper" aria-live="polite">
                        <div class="flex items-end justify-between gap-3">
                            <span class="text-xs font-extrabold uppercase tracking-[.14em] text-gold-300">Estimation</span>
                            <strong v-if="preview.total !== null" class="font-display text-2xl font-extrabold">{{ money(preview.total) }}</strong>
                            <strong v-else class="font-display text-base font-extrabold text-gold-300">Prix confirmé après étude</strong>
                        </div>
                        <p v-if="preview.total !== null && preview.quantity > 1" class="mt-1 text-xs text-paper/60">{{ preview.quantity }} × {{ money(preview.unit) }}</p>
                        <p class="mt-2 border-t border-white/10 pt-2 text-xs text-paper/60">Le prix définitif est confirmé par l’atelier avant la production.</p>
                    </div>

                    <div class="flex flex-col gap-2.5 sm:flex-row">
                        <button class="btn-gold flex-1" :disabled="form.processing">
                            <Icon name="shopping-cart" :size="18" /> {{ isQuote ? 'Ajouter à ma demande' : 'Ajouter au panier' }}
                        </button>
                        <Link :href="`/devis?service=${encodeURIComponent(product.name)}`" class="btn-line">Demander un devis</Link>
                    </div>
                </form>
            </div>
        </div>

        <section v-if="related.length">
            <h2 class="mb-5 text-2xl font-extrabold">Dans la même famille</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"><ProductCard v-for="p in related" :key="p.id" :product="p" /></div>
        </section>
    </div>
</template>
