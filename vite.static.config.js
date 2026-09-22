import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

// Static build for Netlify (no PHP). Laravel keeps using vite.config.js.
export default defineConfig({
    plugins: [vue(), tailwindcss()],
    publicDir: false,
    build: {
        outDir: 'dist',
        emptyOutDir: true,
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});
