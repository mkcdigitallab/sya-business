<script setup>
import Icon from './Icon.vue';

const props = defineProps({
    modelValue: { type: Array, required: true },
    fields: { type: Array, required: true },
    required: { type: Array, default: () => [] },
    sizes: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
    max: { type: Number, default: 80 },
});
const emit = defineEmits(['update:modelValue']);

const blank = () => ({ name: '', number: '', size: props.sizes[Math.floor(props.sizes.length / 2)] ?? '' });
const update = (i, key, value) => emit('update:modelValue', props.modelValue.map((row, n) => (n === i ? { ...row, [key]: value } : row)));
const add = () => props.modelValue.length < props.max && emit('update:modelValue', [...props.modelValue, blank()]);
const remove = (i) => emit('update:modelValue', props.modelValue.filter((_, n) => n !== i));
const err = (i, key) => props.errors[`roster.${i}.${key}`];
const has = (key) => props.fields.includes(key);

// Colonnes sur écran large : # · nom · numéro · taille · retirer (les colonnes absentes disparaissent).
const wide = () => `24px ${has('name') ? 'minmax(0,1.6fr)' : ''} ${has('number') ? '76px' : ''} ${has('size') ? '88px' : ''} 36px`;
</script>

<template>
    <div>
        <div class="overflow-hidden rounded-xl border border-line bg-white">
            <!-- En-têtes : seulement sur écran large (sur mobile, les placeholders suffisent) -->
            <div class="hidden items-center gap-2 border-b border-line bg-paper2 px-3 py-2 text-[11px] font-extrabold uppercase tracking-wider text-muted sm:grid" :style="{ gridTemplateColumns: wide() }">
                <span>#</span>
                <span v-if="has('name')">Nom {{ required.includes('name') ? '' : '(facultatif)' }}</span>
                <span v-if="has('number')">N°</span>
                <span v-if="has('size')">Taille</span>
                <span />
            </div>

            <div v-for="(row, i) in modelValue" :key="i" class="border-b border-line/70 px-3 py-2.5 last:border-0">
                <!-- Mobile : nom + croix sur la première ligne, numéro + taille dessous. Écran large : tout sur une ligne. -->
                <div class="roster-row grid items-center gap-2" :style="{ '--wide': wide() }">
                    <span class="text-xs font-bold text-muted sm:col-start-1">{{ i + 1 }}</span>

                    <input v-if="has('name')" :value="row.name" maxlength="20" :placeholder="required.includes('name') ? 'NOM' : 'NOM (facultatif)'" aria-label="Nom"
                           class="input !px-3 !py-2 uppercase" :class="{ 'input-error': err(i, 'name') }" @input="update(i, 'name', $event.target.value)" />

                    <div class="col-span-2 col-start-2 flex gap-2 sm:contents" :class="{ 'col-start-2': true }">
                        <input v-if="has('number')" :value="row.number" inputmode="numeric" maxlength="3" placeholder="N°" aria-label="Numéro"
                               class="input w-20 !px-3 !py-2 text-center sm:w-auto" :class="{ 'input-error': err(i, 'number') }" @input="update(i, 'number', $event.target.value.replace(/\D/g, ''))" />
                        <select v-if="has('size')" :value="row.size" aria-label="Taille" class="input w-24 !px-2 !py-2 sm:w-auto" :class="{ 'input-error': err(i, 'size') }" @change="update(i, 'size', $event.target.value)">
                            <option v-for="s in sizes" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>

                    <button type="button" class="roster-remove grid h-9 w-9 place-items-center rounded-lg text-muted transition hover:bg-danger/10 hover:text-danger disabled:opacity-30"
                            :disabled="modelValue.length <= 1" :aria-label="`Retirer la ligne ${i + 1}`" @click="remove(i)">
                        <Icon name="x" :size="16" />
                    </button>
                </div>
                <p v-if="err(i, 'name') || err(i, 'number') || err(i, 'size')" class="error pl-8">{{ err(i, 'name') || err(i, 'number') || err(i, 'size') }}</p>
            </div>
        </div>

        <div class="mt-3 flex items-center justify-between">
            <button type="button" class="btn-line btn-sm" :disabled="modelValue.length >= max" @click="add"><Icon name="plus" :size="15" /> Ajouter une ligne</button>
            <span class="text-xs font-semibold text-muted">{{ modelValue.length }} {{ modelValue.length > 1 ? 'lignes' : 'ligne' }}</span>
        </div>
        <p v-if="errors.roster" class="error">{{ errors.roster }}</p>
    </div>
</template>

<style scoped>
/* Mobile : 3 colonnes (#, champ, croix) ; la croix reste sur la première ligne. */
.roster-row { grid-template-columns: 24px 1fr 36px; }
.roster-remove { grid-column: 3; grid-row: 1; }

@media (min-width: 640px) {
    .roster-row { grid-template-columns: var(--wide); }
    .roster-remove { grid-column: -2; grid-row: 1; }
}
</style>
