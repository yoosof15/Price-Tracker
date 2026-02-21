/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Vazirmatn', 'sans-serif'],
            },
            colors: {
                primaryRed: '#ff4b2b',
                primaryPurple: '#7d00fc',
                primaryGreen: '#71d957',
                darkBackground: '#1a202c',
                darkCard: '#2d3748',
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
