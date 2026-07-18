import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            colors: {
                plum: {
                    DEFAULT: '#1a0b16',
                    light: '#2d1826',
                    soft: '#7a4a68',
                    pill: '#3d2436',
                    header: '#1a0b16',
                },
                joy: '#f06292',
                'joy-deep': '#e84d8a',
                terracotta: '#e65100',
                'footer-pink': '#ff79c6',
                cream: '#f5f2ef',
                market: {
                    DEFAULT: '#0f766e',
                    hover: '#0d9488',
                    muted: '#ccfbf1',
                },
                surface: '#fafaf9',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
