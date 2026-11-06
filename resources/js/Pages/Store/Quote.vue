<script setup>
import { Head } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import InquiryForm from '../../Components/InquiryForm.vue';
import PageHeader from '../../Components/PageHeader.vue';

defineProps({ service: { type: String, default: '' }, services: { type: Array, default: () => [] } });
const points = [
    ['pen-tool', 'Infographie, logo, visuels', 'Décrivez le besoin : nous vous répondons avec un prix.'],
    ['printer', 'Grand format et grosses quantités', 'Bâches, vinyle, séries de textile : nous chiffrons selon votre projet.'],
    ['shirt', 'Équipes et associations', 'Maillots et flocage pour tout un groupe.'],
];
</script>

<template>
    <Head title="Demander un devis" />

    <div class="grid items-start gap-10 lg:grid-cols-[1fr_1.1fr]">
        <div class="space-y-6">
            <PageHeader eyebrow="Devis" title="Parlez-nous de votre projet" subtitle="Pour un besoin qui ne rentre pas dans un panier, ou simplement pour demander un prix avant de vous décider." />
            <ul class="space-y-4">
                <li v-for="[icon, title, text] in points" :key="title" class="flex gap-4">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gold-100 text-gold-700"><Icon :name="icon" :size="22" /></span>
                    <span><b class="block">{{ title }}</b><span class="text-sm text-muted">{{ text }}</span></span>
                </li>
            </ul>
        </div>
        <InquiryForm kind="quote" :service="service" :services="services" />
    </div>
</template>
