<script setup>
import { ref, watch } from 'vue';
import { familyIcon } from '../lib/product';
import Icon from './Icon.vue';

const props = defineProps({
    src: { type: String, default: null },
    name: { type: String, default: '' },
    family: { type: String, default: '' },
    iconSize: { type: [Number, String], default: 46 },
});

// Pas de photo, ou photo introuvable (lien externe cassé) : un repère à la marque plutôt qu'une image brisée.
const failed = ref(false);
watch(() => props.src, () => (failed.value = false));
</script>

<template>
    <img v-if="src && !failed" :src="src" :alt="name" loading="lazy" class="h-full w-full object-cover" @error="failed = true" />
    <div v-else class="halftone grid h-full w-full place-items-center bg-gradient-to-br from-gold-100 via-paper2 to-gold-200 text-gold-700" role="img" :aria-label="name">
        <Icon :name="familyIcon(family)" :size="iconSize" :stroke="1.5" />
    </div>
</template>
