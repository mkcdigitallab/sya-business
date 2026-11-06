<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import InquiryForm from '../../Components/InquiryForm.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { prettyPhone } from '../../lib/format';

defineProps({ service: { type: String, default: '' }, services: { type: Array, default: () => [] } });
const site = computed(() => usePage().props.site);
</script>

<template>
    <Head title="Contact" />

    <div class="grid items-start gap-10 lg:grid-cols-[1fr_1.1fr]">
        <div class="space-y-6">
            <PageHeader eyebrow="Contact" title="Une question ? Parlons-en." subtitle="Le plus rapide : un appel. Vous pouvez aussi nous écrire ci-contre." />
            <ul class="space-y-4">
                <li class="flex gap-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gold-100 text-gold-700"><Icon name="phone" :size="22" /></span><span><b class="block">Téléphone</b><a :href="`tel:+${site.phone}`" class="font-display text-xl font-extrabold text-gold-700 hover:underline">{{ prettyPhone('+' + site.phone) }}</a></span></li>
                <li v-if="site.email" class="flex gap-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gold-100 text-gold-700"><Icon name="mail" :size="22" /></span><span><b class="block">Email</b><a :href="`mailto:${site.email}`" class="text-muted hover:text-gold-700">{{ site.email }}</a></span></li>
                <li v-if="site.address" class="flex gap-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gold-100 text-gold-700"><Icon name="map-pin" :size="22" /></span><span><b class="block">Atelier</b><span class="text-muted">{{ site.address }}</span></span></li>
                <li v-if="site.hours" class="flex gap-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gold-100 text-gold-700"><Icon name="clock" :size="22" /></span><span><b class="block">Horaires</b><span class="text-muted">{{ site.hours }}</span></span></li>
            </ul>
        </div>
        <InquiryForm kind="contact" :service="service" :services="services" />
    </div>
</template>
