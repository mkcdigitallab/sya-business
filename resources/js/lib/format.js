/** 1500 → « 1 500 FCFA » */
export function money(value) {
    return new Intl.NumberFormat('fr-FR').format(Number(value) || 0).replace(/[\u202f\u00a0]/g, ' ') + ' FCFA';
}

export function formatDate(value, withTime = true) {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric', month: 'long', year: 'numeric',
        ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    }).format(d);
}

export function formatShortDate(value) {
    if (!value) return '';
    return new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(value));
}

export function plural(n, one, many) {
    return `${n} ${n > 1 ? many : one}`;
}

/** +221781284366 → « +221 78 128 43 66 » */
export function prettyPhone(value) {
    const digits = String(value || '').replace(/\D/g, '');
    const m = digits.match(/^221(\d{2})(\d{3})(\d{2})(\d{2})$/);
    return m ? `+221 ${m[1]} ${m[2]} ${m[3]} ${m[4]}` : String(value || '');
}

export function fileSize(bytes) {
    if (bytes < 1024 * 1024) return Math.max(1, Math.round(bytes / 1024)) + ' Ko';
    return (bytes / 1024 / 1024).toFixed(1).replace('.', ',') + ' Mo';
}
