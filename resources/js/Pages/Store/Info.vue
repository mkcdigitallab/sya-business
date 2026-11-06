<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import PageHeader from '../../Components/PageHeader.vue';

const deposit = computed(() => usePage().props.site.depositPercent);

const steps = [
    ['Vous choisissez et personnalisez', 'Sur chaque fiche, réglez tailles, noms, numéros, dimensions ou options. L’estimation se met à jour en direct.'],
    ['Vous envoyez votre demande', 'Aucun paiement à ce stade. Vous pouvez joindre votre logo ou votre visuel. Vous recevez un lien de suivi.'],
    ['Nous confirmons le prix', 'Nous vérifions votre demande, puis nous confirmons le prix définitif et le délai. Si le prix change, vous acceptez le devis en ligne.'],
    ['Vous validez la maquette', 'Pour les impressions et le flocage, nous vous envoyons une maquette. La production ne démarre qu’avec votre accord.'],
    ['Nous fabriquons, vous récupérez', 'Nous vous prévenons quand la commande est prête : retrait à la boutique ou livraison.'],
];

const faq = computed(() => [
    ['Dois-je payer à la commande ?', `Non. Le paiement commence à la confirmation : un acompte de ${deposit.value} %, puis le solde à la remise. Vous pouvez régler par Wave, Orange Money ou en espèces.`],
    ['Le prix affiché est-il définitif ?', 'C’est une estimation. L’atelier confirme le prix définitif avant la production, notamment pour les dimensions, les quantités et les articles sur devis.'],
    ['Que se passe-t-il si je ne suis pas d’accord avec la maquette ?', 'Vous demandez une modification en précisant ce qu’il faut changer. Nous vous renvoyons une nouvelle maquette. Rien n’est imprimé avant votre validation.'],
    ['Quels fichiers puis-je envoyer ?', 'Images (JPG, PNG, WebP), PDF, SVG, et fichiers de graphisme (AI, EPS, CDR) ou archive ZIP. 5 fichiers maximum, 10 Mo chacun.'],
    ['Puis-je annuler ma commande ?', 'Oui, seul en ligne tant que la commande n’est pas confirmée. Ensuite, appelez-nous : la production a peut-être déjà commencé.'],
    ['Comment connaître les délais ?', 'Ils dépendent du produit et de la quantité. Nous vous les confirmons avec le prix.'],
    ['Retrait ou livraison ?', 'Les deux sont possibles. Les frais de livraison éventuels sont confirmés avec le devis.'],
    ['Et si je n’ai pas encore de logo ?', 'Nous créons logos, infographies et visuels. Décrivez votre projet dans une demande de devis.'],
]);
const open = ref(0);
</script>

<template>
    <Head title="Comment ça marche" />

    <div class="mx-auto max-w-3xl space-y-12">
        <PageHeader eyebrow="Informations" title="Comment ça marche" subtitle="De votre demande à la remise de votre commande : ce que vous devez savoir." />

        <ol class="space-y-5">
            <li v-for="([title, text], i) in steps" :key="title" class="flex gap-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-gold-500 font-display font-extrabold text-ink">{{ i + 1 }}</span>
                <span class="pt-1"><b class="block text-lg font-extrabold">{{ title }}</b><span class="text-muted">{{ text }}</span></span>
            </li>
        </ol>

        <section>
            <h2 class="mb-4 text-2xl font-extrabold">Questions fréquentes</h2>
            <div class="divide-y divide-line rounded-2xl border border-line bg-white">
                <div v-for="([q, a], i) in faq" :key="q">
                    <button class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-bold" :aria-expanded="open === i" @click="open = open === i ? -1 : i">
                        {{ q }} <Icon :name="open === i ? 'chevron-up' : 'chevron-down'" :size="18" class="text-gold-700" />
                    </button>
                    <p v-if="open === i" class="px-5 pb-5 text-sm leading-relaxed text-muted">{{ a }}</p>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap justify-center gap-3">
            <Link href="/boutique" class="btn-gold">Voir la boutique</Link>
            <Link href="/contact" class="btn-line">Une autre question ?</Link>
        </div>
    </div>
</template>
