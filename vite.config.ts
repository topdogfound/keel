import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import babel from '@rolldown/plugin-babel';
import tailwindcss from '@tailwindcss/vite';
import react, { reactCompilerPreset } from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defaultAllowedOrigins } from 'vite';
import { defineConfig, lazyPlugins } from 'vite-plus';

// Host ports are defined once, in .env. compose.yaml forwards VITE_PORT into
// the container so this file can read it; the fallback matches compose's own
// default. Vite is published on the same port inside and out, because the
// browser on the host connects to the HMR server directly.
const vitePort = Number(process.env.VITE_PORT ?? 8766);

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        react(),
        babel({
            presets: [reactCompilerPreset()],
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ]),
    server: {
        // Vite runs inside the container; the browser is on the host.
        host: '0.0.0.0', // bind all interfaces, not just container loopback
        port: vitePort,
        strictPort: true,
        origin: `http://localhost:${vitePort}`, // keeps 0.0.0.0 out of public/hot
        // laravel-vite-plugin defaults `cors.origin` to `server.origin` when the
        // latter is set, which would otherwise only allow requests whose Origin
        // header is the Vite port itself. The page is served from APP_PORT, so
        // without this every @vite/client / HMR / module request is cross-origin
        // and gets rejected — killing HMR and full-reload-on-backend-change alike.
        // Restore Vite's own default (any localhost/127.0.0.1 port) instead.
        cors: { origin: defaultAllowedOrigins },
        hmr: {
            host: 'localhost', // where the browser should connect back to
        },
        watch: {
            // Some Docker Desktop bind mounts don't propagate inotify events.
            // Opt in with VITE_USE_POLLING=1 rather than paying for it always.
            usePolling: !!process.env.VITE_USE_POLLING,
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
            // Vendor-published assets. Filament publishes its compiled JS and
            // CSS into public/, and format-checking someone else's build output
            // fails CI over code this project does not own. lint.ignorePatterns
            // already excluded these; fmt did not.
            'public/**',
            'vendor/**',
            'node_modules/**',
            'bootstrap/ssr/**',
            // Generated at build time by the TypeScript transformer.
            'resources/js/types/generated.d.ts',
            'resources/js/types/typescript-transformer-manifest.json',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            entryPoint: 'resources/css/app.css',
        },
    },
});
