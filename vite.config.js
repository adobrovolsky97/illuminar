import {defineConfig} from 'vite';
import vue from '@vitejs/plugin-vue';

// Assets are served by the package itself (IlluminarController@asset), so the file names are fixed.
export default defineConfig({
    plugins: [vue()],
    publicDir: false,
    build: {
        outDir: 'public',
        emptyOutDir: false,
        cssCodeSplit: false,
        rollupOptions: {
            input: 'src/resources/js/app.js',
            output: {
                entryFileNames: 'app.js',
                chunkFileNames: 'app-[name].js',
                assetFileNames: (asset) => (asset.names?.[0] ?? asset.name ?? '').endsWith('.css') ? 'app.css' : '[name][extname]',
            },
        },
    },
});
