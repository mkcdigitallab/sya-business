<script setup>
import { computed } from 'vue';
import icons from '../lib/icons';

const props = defineProps({
    name: { type: String, required: true },
    size: { type: [Number, String], default: 20 },
    stroke: { type: [Number, String], default: 2 },
});

const nodes = computed(() => icons[props.name] ?? []);
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        :width="size" :height="size" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" :stroke-width="stroke" stroke-linecap="round" stroke-linejoin="round"
        aria-hidden="true" class="shrink-0"
    >
        <component :is="tag" v-for="([tag, attrs], i) in nodes" :key="i" v-bind="attrs" />
    </svg>
</template>
