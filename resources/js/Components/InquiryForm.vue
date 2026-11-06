<script setup>
import { useForm } from '@inertiajs/vue3';
import Icon from './Icon.vue';

const props = defineProps({
    kind: { type: String, required: true }, // 'quote' | 'contact'
    service: { type: String, default: '' },
    services: { type: Array, default: () => [] },
});

const form = useForm({ name: '', phone: '', email: '', service: props.service ?? '', message: '' });
const endpoint = props.kind === 'quote' ? '/devis' : '/contact';
const submit = () => form.post(endpoint, { preserveScroll: true, onSuccess: () => form.reset('message') });
</script>

<template>
    <form class="card space-y-4" novalidate @submit.prevent="submit">
        <div v-if="form.wasSuccessful" class="flex gap-3 rounded-xl border border-ok/30 bg-ok/5 p-4 text-sm" role="status">
            <Icon name="circle-check" :size="22" class="mt-0.5 shrink-0 text-ok" />
            <div><b class="block">{{ kind === 'quote' ? 'Demande de devis envoyée' : 'Message envoyé' }}</b><span class="text-muted">Nous vous répondons par téléphone dès que possible.</span></div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="q-name">Nom</label>
                <input id="q-name" v-model="form.name" class="input" :class="{ 'input-error': form.errors.name }" autocomplete="name" />
                <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="label" for="q-phone">Téléphone</label>
                <input id="q-phone" v-model="form.phone" type="tel" class="input" :class="{ 'input-error': form.errors.phone }" placeholder="+221 77 123 45 67" autocomplete="tel" />
                <p v-if="form.errors.phone" class="error">{{ form.errors.phone }}</p>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="q-email">Email <span class="font-normal text-muted">(facultatif)</span></label>
                <input id="q-email" v-model="form.email" type="email" class="input" :class="{ 'input-error': form.errors.email }" autocomplete="email" />
                <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
            </div>
            <div>
                <label class="label" for="q-service">{{ kind === 'quote' ? 'Service concerné' : 'Sujet' }} <span class="font-normal text-muted">(facultatif)</span></label>
                <select id="q-service" v-model="form.service" class="input">
                    <option value="">{{ kind === 'quote' ? 'Autre / je ne sais pas' : 'Autre' }}</option>
                    <option v-for="s in services" :key="s.slug" :value="s.name">{{ s.name }}</option>
                </select>
            </div>
        </div>
        <div>
            <label class="label" for="q-message">{{ kind === 'quote' ? 'Décrivez votre projet' : 'Votre message' }}</label>
            <textarea id="q-message" v-model="form.message" rows="5" maxlength="2000" class="input" :class="{ 'input-error': form.errors.message }" :placeholder="kind === 'quote' ? 'Quantité, dimensions, couleurs, date souhaitée…' : 'Comment pouvons-nous vous aider ?'" />
            <p v-if="form.errors.message" class="error">{{ form.errors.message }}</p>
        </div>
        <button class="btn-gold btn-block sm:w-auto" :disabled="form.processing"><Icon name="mail" :size="18" /> {{ form.processing ? 'Envoi…' : kind === 'quote' ? 'Envoyer ma demande de devis' : 'Envoyer le message' }}</button>
    </form>
</template>
