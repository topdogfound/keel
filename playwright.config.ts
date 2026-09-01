import { defineConfig, devices } from '@playwright/test';

/**
 * Browser tests run *inside* the app container, so the app is reachable on
 * localhost:80 there -- the same place Sail's supervisor serves it. Nothing
 * here should ever need a browser installed on the host.
 *
 * APP_PORT is deliberately not used. That is the *host* port (8765) which does
 * not exist inside the container, so reading APP_URL here would break every
 * test the moment anything injected it. Override with PLAYWRIGHT_BASE_URL if
 * you ever need to point the suite somewhere else.
 *
 * Run with `./keel e2e`.
 */
export default defineConfig({
    testDir: './tests/Browser',
    // A failing E2E test is usually a real regression, but flakes happen in CI.
    retries: process.env.CI ? 1 : 0,
    // The container is not a big machine; parallel browsers make it flakier.
    workers: 1,
    reporter: process.env.CI ? [['github'], ['list']] : [['list']],
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://localhost',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
