<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';

defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    back: { type: Object, default: null }, // { href, label }
});
</script>

<template>
    <header>
        <Link v-if="back" :href="back.href" class="mb-3 inline-flex items-center gap-1.5 text-sm font-bold text-muted hover:text-ink">
            <Icon name="arrow-left" :size="16" /> {{ back.label }}
        </Link>
        <span v-if="eyebrow" class="eyebrow">{{ eyebrow }}</span>
        <h1 class="mt-2 text-3xl font-extrabold leading-tight sm:text-4xl">{{ title }}</h1>
        <p v-if="subtitle" class="mt-2 max-w-2xl text-muted">{{ subtitle }}</p>
        <slot />
    </header>
</template>
