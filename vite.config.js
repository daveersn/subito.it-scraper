import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css'
            ],
            refresh: [...refreshPaths, 'app/Livewire/**', 'app/Filament/**'],
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/',
        },
    },
});
