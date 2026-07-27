import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import fs from 'node:fs';
import path from 'node:path';

function normalizeLaravelManifestKeys() {
    return {
        name: 'normalize-laravel-manifest-keys',
        closeBundle() {
            const manifestPath = path.resolve('public/build/manifest.json');

            if (!fs.existsSync(manifestPath)) {
                return;
            }

            const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
            const normalizedManifest = Object.fromEntries(Object.entries(manifest).map(([key, value]) => {
                const normalizedKey = key.replaceAll('\\', '/');
                const resourcesIndex = normalizedKey.indexOf('resources/');
                const nodeModulesIndex = normalizedKey.indexOf('node_modules/');

                if (resourcesIndex !== -1) {
                    return [normalizedKey.slice(resourcesIndex), value];
                }

                if (nodeModulesIndex !== -1) {
                    return [normalizedKey.slice(nodeModulesIndex), value];
                }

                return [key, value];
            }));

            fs.writeFileSync(manifestPath, `${JSON.stringify(normalizedManifest, null, 2)}\n`);
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        normalizeLaravelManifestKeys(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
