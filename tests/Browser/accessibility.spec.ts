import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { logIn } from './support/auth';

/**
 * Accessibility checks ride the browser suite that already exists, so they cost
 * almost nothing -- and they are far cheaper here than as an audit-driven
 * retrofit once the UI has grown.
 *
 * Scoped to serious and critical violations: wcag2a/wcag2aa catch real barriers,
 * while the full rule set produces enough advisory noise to get switched off.
 */
async function scan(page: import('@playwright/test').Page) {
    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa'])
        .analyze();

    return results.violations.filter(
        (v) => v.impact === 'serious' || v.impact === 'critical',
    );
}

test('the login page has no serious accessibility violations', async ({
    page,
}) => {
    await page.goto('/login');

    const violations = await scan(page);

    expect(violations.map((v) => `${v.id}: ${v.help}`)).toEqual([]);
});

test('the registration page has no serious accessibility violations', async ({
    page,
}) => {
    await page.goto('/register');

    const violations = await scan(page);

    expect(violations.map((v) => `${v.id}: ${v.help}`)).toEqual([]);
});

test('the signed-out home page has no serious accessibility violations', async ({
    page,
}) => {
    await page.goto('/');

    const violations = await scan(page);

    expect(violations.map((v) => `${v.id}: ${v.help}`)).toEqual([]);
});

test('the signed-in home page has no serious accessibility violations', async ({
    page,
}) => {
    await logIn(page, 'member@keel.test');
    await expect(page).not.toHaveURL(/\/login/);

    const violations = await scan(page);

    expect(violations.map((v) => `${v.id}: ${v.help}`)).toEqual([]);
});
