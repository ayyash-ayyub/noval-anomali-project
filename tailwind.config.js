import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Share Tech Mono"', ...defaultTheme.fontFamily.mono],
                mono: ['"Share Tech Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                matrix: {
                    black: '#000000',
                    surface: '#050805',
                    panel: '#0a0f0a',
                    border: '#0f3d0f',
                    green: {
                        DEFAULT: '#00ff41',
                        dim: '#00b32d',
                        dark: '#008f11',
                        deep: '#003b00',
                        glow: '#39ff14',
                    },
                },
            },
            boxShadow: {
                'matrix-glow': '0 0 6px rgba(0, 255, 65, 0.55), 0 0 18px rgba(0, 255, 65, 0.25)',
                'matrix-glow-lg': '0 0 12px rgba(0, 255, 65, 0.6), 0 0 32px rgba(0, 255, 65, 0.3)',
            },
            animation: {
                'blink-cursor': 'blink-cursor 1s steps(1) infinite',
            },
            keyframes: {
                'blink-cursor': {
                    '0%, 49%': { opacity: 1 },
                    '50%, 100%': { opacity: 0 },
                },
            },
        },
    },

    plugins: [forms],
};
