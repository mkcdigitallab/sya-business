import { onBeforeUnmount, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Recharge périodiquement certaines données d'une page tant que l'onglet est visible.
 * `only` limite le rechargement aux props utiles ; `enabled` permet de s'arrêter (commande terminée).
 */
export function usePolling({ only = [], every = 30000, enabled = () => true } = {}) {
    let timer;

    function refresh() {
        if (document.visibilityState !== 'visible' || !enabled()) return;
        router.reload({ only, preserveScroll: true, preserveState: true });
    }

    onMounted(() => {
        timer = window.setInterval(refresh, every);
        document.addEventListener('visibilitychange', refresh);
    });
    onBeforeUnmount(() => {
        window.clearInterval(timer);
        document.removeEventListener('visibilitychange', refresh);
    });
}
