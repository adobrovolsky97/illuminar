module.exports = {
    purge: {
        mode: 'layers',
        content: [
            "./src/resources/**/*.blade.php",
            "./src/resources/**/*.{vue,js,ts,jsx,tsx}",
        ],
        options: {
            safelist: ['dark']
        }
    },
    darkMode: 'class',
    theme: {
    },
    variants: {
        extend: {},
    },
    plugins: [],
};
