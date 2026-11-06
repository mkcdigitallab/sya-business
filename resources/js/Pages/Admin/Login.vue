<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import Logo from '../../Components/Logo.vue';

const form = useForm({ email: '', password: '' });
const submit = () => form.post('/admin/connexion', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Administration" />

    <main class="halftone grid min-h-screen place-items-center bg-paper2 px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-6 flex justify-center"><Logo /></div>
            <form class="card space-y-4" novalidate @submit.prevent="submit">
                <h1 class="flex items-center gap-2 font-display text-2xl font-extrabold"><Icon name="lock" :size="22" class="text-gold-700" /> Administration</h1>
                <div>
                    <label class="label" for="email">Email</label>
                    <input id="email" v-model="form.email" type="email" class="input" :class="{ 'input-error': form.errors.email }" autocomplete="username" required />
                    <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="label" for="password">Mot de passe</label>
                    <input id="password" v-model="form.password" type="password" class="input" autocomplete="current-password" required />
                </div>
                <button class="btn-dark btn-block" :disabled="form.processing">{{ form.processing ? 'Connexion…' : 'Se connecter' }}</button>
            </form>
        </div>
    </main>
</template>
