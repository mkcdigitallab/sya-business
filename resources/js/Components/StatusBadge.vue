<script setup>
import { computed } from 'vue';

const props = defineProps({ status: { type: String, required: true }, label: { type: String, required: true } });

// Une couleur par famille d'état : attente (or), action du client (bleu-gris), fabrication, fin, annulation.
const tone = computed(() => ({
    received: 'bg-gold-100 text-gold-800',
    quoted: 'bg-sky-100 text-sky-800',
    confirmed: 'bg-gold-200 text-gold-900',
    proofing: 'bg-sky-100 text-sky-800',
    production: 'bg-violet-100 text-violet-800',
    ready: 'bg-emerald-100 text-emerald-800',
    completed: 'bg-ok text-white',
    cancelled: 'bg-stone-200 text-stone-700',
}[props.status] ?? 'bg-stone-100 text-stone-700'));
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-extrabold" :class="tone">{{ label }}</span>
</template>
