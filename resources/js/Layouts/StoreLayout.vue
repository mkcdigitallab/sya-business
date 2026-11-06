<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Icon from '../Components/Icon.vue';
import Logo from '../Components/Logo.vue';
import { prettyPhone } from '../lib/format';

const page = usePage();
const site = computed(() => page.props.site);
const families = computed(() => page.props.families);
const cartCount = computed(() => page.props.cartCount);
const menu = ref(false);
const toast = ref({ text: '', tone: 'ok' });
let timer;

const path = computed(() => page.url.split('?')[0]);
const query = computed(() => page.url.split('?')[1] ?? '');

// Un lien est « actif » quand on est sur sa page (ou sur sa famille).
const nav = computed(() => [
    { href: '/boutique', label: 'Boutique', active: path.value === '/boutique' && !query.value.includes('famille') || path.value.startsWith('/produit') },
    ...families.value.filter((f) => f.slug !== 'mode').map((f) => ({
        href: `/boutique?famille=${f.slug}`, label: f.short,
        active: path.value === '/boutique' && query.value.includes(`famille=${f.slug}`),
    })),
    { href: '/informations', label: 'Infos', active: path.value === '/informations' },
]);

watch(() => page.url, () => (menu.value = false));
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
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <div class="bg-ink text-paper">
            <div class="mx-auto flex max-w-[1180px] items-center justify-between gap-4 px-5 py-2 text-xs">
                <span class="hidden text-paper/70 sm:block">Maillots, flocage, impression et création graphique</span>
                <a :href="`tel:+${site.phone}`" class="flex items-center gap-2 font-bold text-gold-300 hover:text-gold-200">
                    <Icon name="phone" :size="14" /> Appelez-nous : {{ prettyPhone('+' + site.phone) }}
                </a>
            </div>
        </div>

        <header class="sticky top-0 z-30 border-b border-line bg-paper/90 backdrop-blur-xl">
            <div class="mx-auto flex h-[72px] max-w-[1180px] items-center justify-between gap-6 px-5">
                <Link href="/" aria-label="Siya Business — accueil"><Logo /></Link>

                <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigation principale">
                    <Link v-for="item in nav" :key="item.href" :href="item.href"
                          class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-bold transition"
                          :class="item.active ? 'bg-gold-100 text-ink' : 'text-muted hover:text-ink'">{{ item.label }}</Link>
                </nav>

                <div class="flex items-center gap-2">
                    <Link href="/devis" class="btn-dark btn-sm hidden whitespace-nowrap sm:inline-flex">Demander un devis</Link>
                    <Link href="/panier" class="relative grid h-11 w-11 place-items-center rounded-xl border border-line bg-white transition hover:border-gold-500" aria-label="Voir mon panier">
                        <Icon name="shopping-cart" :size="20" />
                        <span v-if="cartCount" class="absolute -right-1.5 -top-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-gold-500 px-1 text-[11px] font-extrabold text-ink">{{ cartCount }}</span>
                    </Link>
                    <button class="grid h-11 w-11 place-items-center rounded-xl border border-line bg-white lg:hidden" :aria-expanded="menu" aria-label="Menu" @click="menu = !menu">
                        <Icon :name="menu ? 'x' : 'menu'" :size="20" />
                    </button>
                </div>
            </div>

            <nav v-if="menu" class="border-t border-line bg-paper px-5 pb-4 lg:hidden" aria-label="Menu mobile">
                <Link v-for="item in nav" :key="item.href" :href="item.href" class="flex items-center justify-between border-b border-line/70 py-3 text-[15px] font-bold" :class="item.active ? 'text-gold-700' : ''">
                    {{ item.label }} <Icon name="chevron-right" :size="16" class="text-muted" />
                </Link>
                <Link href="/devis" class="btn-dark mt-4 w-full">Demander un devis</Link>
            </nav>
        </header>

        <main :key="path" class="enter mx-auto w-full max-w-[1180px] flex-1 px-5 py-8 sm:py-10">
            <slot />
        </main>

        <footer class="mt-8 bg-ink text-paper">
            <div class="halftone-dark">
                <div class="mx-auto grid max-w-[1180px] gap-10 px-5 py-12 md:grid-cols-[1.4fr_1fr_1fr_1.2fr]">
                    <div>
                        <Logo dark />
                        <p class="mt-4 max-w-xs text-sm leading-relaxed text-paper/65">Nous imprimons, floquons et dessinons ce qui fait voir votre équipe et votre activité.</p>
                    </div>
                    <div>
                        <h4 class="mb-3 text-xs font-extrabold uppercase tracking-[.16em] text-gold-300">Boutique</h4>
                        <ul class="space-y-2 text-sm text-paper/75">
                            <li v-for="f in families" :key="f.slug"><Link :href="`/boutique?famille=${f.slug}`" class="hover:text-gold-300">{{ f.label }}</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="mb-3 text-xs font-extrabold uppercase tracking-[.16em] text-gold-300">Aide</h4>
                        <ul class="space-y-2 text-sm text-paper/75">
                            <li><Link href="/informations" class="hover:text-gold-300">Comment ça marche</Link></li>
                            <li><Link href="/devis" class="hover:text-gold-300">Demander un devis</Link></li>
                            <li><Link href="/contact" class="hover:text-gold-300">Nous contacter</Link></li>
                            <li><Link href="/panier" class="hover:text-gold-300">Mon panier</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="mb-3 text-xs font-extrabold uppercase tracking-[.16em] text-gold-300">Contact</h4>
                        <ul class="space-y-2.5 text-sm text-paper/75">
                            <li class="flex gap-2"><Icon name="phone" :size="16" class="mt-0.5 text-gold-400" /><a :href="`tel:+${site.phone}`" class="hover:text-gold-300">{{ prettyPhone('+' + site.phone) }}</a></li>
                            <li v-if="site.email" class="flex gap-2"><Icon name="mail" :size="16" class="mt-0.5 text-gold-400" /><a :href="`mailto:${site.email}`" class="hover:text-gold-300">{{ site.email }}</a></li>
                            <li v-if="site.address" class="flex gap-2"><Icon name="map-pin" :size="16" class="mt-0.5 text-gold-400" />{{ site.address }}</li>
                            <li v-if="site.hours" class="flex gap-2"><Icon name="clock" :size="16" class="mt-0.5 text-gold-400" />{{ site.hours }}</li>
                            <li v-for="(url, name) in site.social" :key="name"><a :href="url" target="_blank" rel="noopener" class="capitalize hover:text-gold-300">{{ name }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="mx-auto flex max-w-[1180px] items-center justify-between gap-4 px-5 py-4 text-xs text-paper/50">
                    <span>© {{ new Date().getFullYear() }} Siya Business</span>
                    <span class="flex items-center gap-1.5" aria-hidden="true" title="Cyan · Magenta · Jaune · Noir">
                        <i class="h-2.5 w-2.5 rounded-full bg-cyan" /><i class="h-2.5 w-2.5 rounded-full bg-magenta" /><i class="h-2.5 w-2.5 rounded-full bg-yellow" /><i class="h-2.5 w-2.5 rounded-full bg-paper" />
                    </span>
                </div>
            </div>
        </footer>

        <Transition enter-active-class="transition duration-200" enter-from-class="translate-y-3 opacity-0" leave-active-class="transition duration-200" leave-to-class="translate-y-3 opacity-0">
            <div v-if="toast.text" class="fixed inset-x-0 bottom-6 z-50 mx-auto flex w-[min(440px,calc(100%-32px))] items-start gap-3 rounded-xl px-4 py-3 text-sm font-semibold shadow-lift"
                 :class="toast.tone === 'error' ? 'bg-danger text-white' : 'bg-ink text-paper'" role="status">
                <Icon :name="toast.tone === 'error' ? 'circle-alert' : 'circle-check'" :size="18" class="mt-0.5" />{{ toast.text }}
            </div>
        </Transition>
    </div>
</template>
