<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Icon from '../Components/Icon.vue';
import Logo from '../Components/Logo.vue';

const page = usePage();
const toast = ref({ text: '', tone: 'ok' });
let timer;

const path = computed(() => page.url.split('?')[0]);
const links = [
    { href: '/admin', label: 'Tableau de bord', icon: 'layout-dashboard', exact: true },
    { href: '/admin/commandes', label: 'Commandes', icon: 'receipt' },
    { href: '/admin/demandes', label: 'Demandes', icon: 'inbox' },
    { href: '/admin/produits', label: 'Catalogue', icon: 'tag' },
];
const isActive = (l) => (l.exact ? path.value === l.href : path.value.startsWith(l.href));

watch(
    () => page.props.flash,
    (flash) => {
        const text = flash?.success || flash?.error;
        if (!text) return;
        toast.value = { text, tone: flash.error && !flash.success ? 'error' : 'ok' };
        window.clearTimeout(timer);
        timer = window.setTimeout(() => (toast.value.text = ''), 4200);
    },
    { immediate: true },
);

const logout = () => router.post('/admin/deconnexion');
</script>

<template>
    <div class="min-h-screen bg-paper2/50">
        <header class="sticky top-0 z-30 border-b border-white/10 bg-ink text-paper">
            <div class="mx-auto flex h-16 max-w-[1280px] items-center justify-between gap-4 px-4">
                <Link href="/admin"><Logo dark /></Link>
                <div class="flex items-center gap-2 text-sm">
                    <span class="hidden text-paper/60 sm:block">{{ page.props.admin?.name }}</span>
                    <Link href="/" class="btn-sm inline-flex min-h-[38px] items-center gap-1.5 rounded-lg px-3 text-xs font-bold text-paper/80 hover:bg-white/10"><Icon name="external-link" :size="14" /> Voir le site</Link>
                    <button class="btn-sm inline-flex min-h-[38px] items-center gap-1.5 rounded-lg bg-white/10 px-3 text-xs font-bold hover:bg-white/20" @click="logout"><Icon name="log-out" :size="14" /> Déconnexion</button>
                </div>
            </div>
            <nav class="mx-auto flex max-w-[1280px] gap-1 overflow-x-auto px-4 pb-2" aria-label="Administration">
                <Link v-for="l in links" :key="l.href" :href="l.href" class="flex shrink-0 items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-bold transition"
                      :class="isActive(l) ? 'bg-gold-500 text-ink' : 'text-paper/70 hover:bg-white/10 hover:text-paper'">
                    <Icon :name="l.icon" :size="16" /> {{ l.label }}
                </Link>
            </nav>
        </header>

        <main :key="path" class="enter mx-auto max-w-[1280px] px-4 py-6"><slot /></main>

        <Transition enter-active-class="transition duration-200" enter-from-class="translate-y-3 opacity-0" leave-active-class="transition duration-200" leave-to-class="translate-y-3 opacity-0">
            <div v-if="toast.text" class="fixed inset-x-0 bottom-6 z-50 mx-auto flex w-[min(440px,calc(100%-32px))] items-start gap-3 rounded-xl px-4 py-3 text-sm font-semibold shadow-lift" :class="toast.tone === 'error' ? 'bg-danger text-white' : 'bg-ink text-paper'" role="status">
                <Icon :name="toast.tone === 'error' ? 'circle-alert' : 'circle-check'" :size="18" class="mt-0.5" />{{ toast.text }}
            </div>
        </Transition>
    </div>
</template>
