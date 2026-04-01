import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'media',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                cream: {
                    50: '#FFFDF9',
                    100: '#FFF8F0',
                    200: '#FFF0DB',
                    300: '#FFE4BF',
                },
                bark: {
                    50: '#FAF6EF',
                    100: '#F0E8D4',
                    200: '#E0D0A9',
                    300: '#C4AD74',
                    400: '#A68B4B',
                    500: '#8B6914',
                    600: '#725612',
                    700: '#5A430E',
                    800: '#42310A',
                    900: '#2A1F06',
                },
                terracotta: {
                    50: '#FDF5F2',
                    100: '#FAE8E1',
                    200: '#F4CFC2',
                    300: '#E8AD98',
                    400: '#D5876D',
                    500: '#C2714F',
                    600: '#A85A3B',
                    700: '#87452D',
                    800: '#663320',
                    900: '#452214',
                },
                moss: {
                    50: '#F3F7F4',
                    100: '#E2ECE4',
                    200: '#C5D9CA',
                    300: '#9FC0A6',
                    400: '#7AA682',
                    500: '#6B8F71',
                    600: '#56735A',
                    700: '#435945',
                    800: '#313F32',
                    900: '#1F271F',
                },
            },
        },
    },

    plugins: [forms],
};
