import { expect, test } from '@playwright/test';
import { logIn } from './support/auth';

/**
 * The /admin boundary is the highest-consequence authorisation rule here:
 * without canAccessPanel every registered customer reaches the staff panel.
 * Pest covers it too; this proves it through a real browser session.
 *
 * The staff panel has no login page of its own -- everyone, staff included,
 * authenticates through the app's single /login flow.
 */
test('a guest is sent to the unified login page', async ({ page }) => {
    await page.goto('/admin');

    await expect(page).toHaveURL(/\/login/);
});

test('an ordinary user is refused the staff panel', async ({ page }) => {
    await logIn(page, 'member@keel.test');
    await expect(page).not.toHaveURL(/\/login/);

    const response = await page.goto('/admin');

    expect(response?.status()).toBe(403);
});

test('a staff user reaches the staff panel', async ({ page }) => {
    await logIn(page, 'support@keel.test');
    await expect(page).not.toHaveURL(/\/login/);

    const response = await page.goto('/admin');

    expect(response?.status()).toBe(200);
    await expect(page).not.toHaveURL(/\/login/);
});
