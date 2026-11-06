<script setup>
import Icon from './Icon.vue';

const props = defineProps({
    modelValue: { type: Number, required: true },
    min: { type: Number, default: 1 },
    max: { type: Number, default: 1000 },
    label: { type: String, default: 'quantité' },
});
const emit = defineEmits(['update:modelValue']);

const set = (v) => emit('update:modelValue', Math.min(props.max, Math.max(props.min, Math.round(Number(v) || props.min))));
</script>

<template>
    <div class="inline-flex items-center rounded-xl border border-line bg-white">
        <button type="button" class="grid h-11 w-11 place-items-center rounded-l-xl text-ink transition hover:bg-gold-100 disabled:opacity-40" :disabled="modelValue <= min" :aria-label="`Diminuer la ${label}`" @click="set(modelValue - 1)">
            <Icon name="minus" :size="16" />
        </button>
        <input :value="modelValue" type="number" inputmode="numeric" :min="min" :max="max" :aria-label="label" class="h-11 w-14 border-0 bg-transparent p-0 text-center font-display text-base font-extrabold focus:ring-0" @change="set($event.target.value)" />
        <button type="button" class="grid h-11 w-11 place-items-center rounded-r-xl text-ink transition hover:bg-gold-100 disabled:opacity-40" :disabled="modelValue >= max" :aria-label="`Augmenter la ${label}`" @click="set(modelValue + 1)">
            <Icon name="plus" :size="16" />
        </button>
    </div>
</template>
