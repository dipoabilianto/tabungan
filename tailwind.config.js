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
            colors: {
                ink: '#12241D',
                paper: '#F5FAF5',
                primary: '#2F6F52',
                'primary-dark': '#1B4633',
                gold: '#C9A227',
                coral: '#C0533E',
                muted: '#6B8577',
                border: '#DCE6DE',
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                display: ['Fraunces', ...defaultTheme.fontFamily.serif],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
        },
    },

    plugins: [forms],
};
