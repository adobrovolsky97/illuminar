module.exports = {
    purge: {
        mode: 'layers',
        content: [
            "./src/resources/**/*.blade.php",
            "./src/resources/**/*.{vue,js,ts,jsx,tsx}",
        ],
    },
    darkMode: false,
    theme: {
    },
    variants: {
        extend: {},
    },
    plugins: [],
};
