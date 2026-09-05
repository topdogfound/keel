import { expect, test } from '@playwright/test';
import { logIn } from './support/auth';

/**
 * The /admin boundary is the highest-consequence authorisation rule here:
 * without canAccessPanel every registered customer reaches the staff panel.
 * Pest covers it too; this proves it through a real browser session.
 */
test('a guest is sent to the panel login', async ({ page }) => {
    await page.goto('/admin');

    await expect(page).toHaveURL(/\/admin\/login/);
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
    await expect(page).not.toHaveURL(/\/admin\/login/);
});
