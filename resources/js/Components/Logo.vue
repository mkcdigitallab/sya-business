<script setup>
defineProps({ dark: { type: Boolean, default: false } });
</script>

<template>
    <span class="flex items-center gap-3">
        <img src="/images/siya-business-logo.jpg" alt="" class="h-11 w-11 rounded-full object-cover ring-2 ring-gold-500/60" width="44" height="44" />
        <span class="leading-none">
            <b class="block font-display text-[19px] font-extrabold tracking-tight" :class="dark ? 'text-paper' : 'text-ink'">Siya <span class="text-gold-600">Business</span></b>
            <small class="mt-1 block whitespace-nowrap text-[9px] font-bold uppercase tracking-[.14em] sm:text-[10px]" :class="dark ? 'text-paper/60' : 'text-muted'">Impression · Textile · Création</small>
        </span>
    </span>
</template>
