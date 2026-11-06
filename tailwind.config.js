import forms from '@tailwindcss/forms'

/*
 * Charte Siya Business — dérivée du logo (badge doré « SB », texte brun très foncé).
 *   ink    le « noir » chaud du logo, pour le texte et les fonds sombres
 *   paper  le fond : un ivoire doux, comme un papier d'imprimerie
 *   gold   la couleur de marque (500 = l'or du logo ; 700 pour le texte sur fond clair, afin de rester lisible)
 *   cmyk   quatre pastilles purement décoratives, clin d'œil à l'impression
 */
export default {
    content: ['./resources/**/*.blade.php', './resources/**/*.js', './resources/**/*.vue'],
    theme: {
        extend: {
            colors: {
                ink: '#1C1710',
                char: '#2A241B',
                paper: '#FAF7F0',
                paper2: '#F2EDE0',
                line: '#E4DCC8',
                muted: '#6B6254',
                gold: {
                    50: '#FBF6E6', 100: '#F5ECD0', 200: '#EBD9A1', 300: '#E0C474', 400: '#D4B25A',
                    500: '#C9A346', 600: '#A8842B', 700: '#8A6A1B', 800: '#6B5214', 900: '#4A380E',
                },
                ok: '#2F7D4F',
                danger: '#B3402F',
                cyan: '#00A0DC',
                magenta: '#E4007C',
                yellow: '#FFD400',
            },
            fontFamily: {
                sans: ['"DM Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                display: ['Manrope', '"DM Sans"', 'ui-sans-serif', 'sans-serif'],
            },
            boxShadow: {
                card: '0 1px 0 rgba(28,23,16,.04), 0 10px 30px -12px rgba(28,23,16,.18)',
                lift: '0 24px 60px -24px rgba(28,23,16,.35)',
            },
        },
    },
    plugins: [forms],
}
