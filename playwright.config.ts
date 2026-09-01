import { defineConfig, devices } from '@playwright/test';

/**
 * Browser tests run *inside* the app container, so the app is reachable on
 * localhost:80 there -- the same place Sail's supervisor serves it. Nothing
 * here should ever need a browser installed on the host.
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
        baseURL: process.env.APP_URL ?? 'http://localhost',
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
