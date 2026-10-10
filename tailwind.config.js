import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Paleta deportiva: césped, negro balón y blanco cal.
// 400 = verde césped base (#41B45C); 600 = césped oscuro (#376F32), el que lleva
// texto blanco (contraste AA). Los verdes claros solo con texto oscuro encima.
const cancha = {
    50: '#EEF8F0',
    100: '#D7EFDC',
    200: '#B0DFBA',
    300: '#80CB91',
    400: '#41B45C',
    500: '#369A4D',
    600: '#376F32',
    700: '#2D5B29',
    800: '#234720',
    900: '#1B3619',
    950: '#0F1F0E',
};

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
                // Texto general: Figtree (libre, SIL OFL). Títulos: Kanit. Números: Teko.
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                titulo: ['Kanit', 'Figtree', ...defaultTheme.fontFamily.sans],
                marcador: ['Teko', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                cancha,
                // Las vistas usan "indigo" como color principal: se redirige a la cancha
                // para cambiar toda la app desde aquí, sin tocar vista por vista.
                indigo: cancha,
                balon: {
                    DEFAULT: '#1F2A2E',
                    700: '#2A383D',
                    800: '#1F2A2E',
                    900: '#151D20',
                },
                cesped: {
                    claro: '#5B9452',
                    oscuro: '#376F32',
                },
            },
        },
    },

    plugins: [forms],
};
