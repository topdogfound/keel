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

// How often the polling watcher restats a file, when polling is on at all.
// Chokidar's own default is 100ms, which is wasted work inside a VM: nobody
// saves a file ten times a second, and every tick crosses the virtiofs boundary.
// 300ms is imperceptible by hand and a third of the traffic. Raise it if the
// container idles hot.
const pollInterval = Number(process.env.VITE_POLL_INTERVAL || 300);

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
            // Docker Desktop mounts the project through a VM, and host inotify
            // events don't cross that boundary reliably — saves show up late, or
            // only after a manual refresh. Opt in with VITE_USE_POLLING=1 in
            // .env rather than paying for it always; compose.yaml forwards it.
            usePolling: !!process.env.VITE_USE_POLLING,
            interval: pollInterval,
            binaryInterval: pollInterval,
            // Vite already ignores .git, node_modules, test-results and its own
            // cache/out dirs, and merges this list with those — so this is only
            // what's left. Everything below is written by the running app rather
            // than by a human, and storage/ is the one that matters: Inertia's
            // devtools drop a JSON file in there on *every request*, which under
            // polling is a steady drip of watcher events for files no module
            // graph references.
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
                '**/storage/**',
                '**/bootstrap/cache/**',
                '**/public/build/**',
                '**/public/hot',
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
