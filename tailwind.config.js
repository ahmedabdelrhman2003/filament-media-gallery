/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ['./resources/views/**/*.blade.php'],
    darkMode: 'class',
    corePlugins: {
        preflight: false,
    },
    theme: {
        extend: {
            colors: {
                primary: {
                    50: 'rgba(var(--primary-50), <alpha-value>)',
                    500: 'rgba(var(--primary-500), <alpha-value>)',
                    950: 'rgba(var(--primary-950), <alpha-value>)',
                },
            },
        },
    },
};
