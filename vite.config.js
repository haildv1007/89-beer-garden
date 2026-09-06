import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/customer.css',
                'resources/css/mobile-navigation.css',
                'resources/css/home-typography.css',
                'resources/css/pos.css',
                'resources/css/kitchen.css',
                'resources/css/admin.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
