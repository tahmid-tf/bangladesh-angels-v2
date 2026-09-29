import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const port = parseInt(env.VITE_PORT ?? '5173', 10);
    const host = env.VITE_DEV_HOST ?? 'localhost';

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/css/ban-wealth.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
        // Real URL required: Vite 5.4+ parses server.origin at config time; the plugin’s
        // "__laravel_vite_placeholder__" breaks new URL() in getAdditionalAllowedHosts.
        server: {
            port,
            origin: `http://${host}:${port}`,
        },
    };
});
