import { expect, test } from '@playwright/test';
import { logIn } from './support/auth';

/**
 * Covers the flows unit tests cannot reach: real navigation, real form posts,
 * real session cookies, and the Inertia bundle actually booting.
 *
 * Relies on the demo seeder, so run after `./keel setup` or `./keel seed`.
 */

test('the login page renders the React bundle', async ({ page }) => {
    await page.goto('/login');

    await expect(page).toHaveTitle(/Keel/);
    await expect(page.getByRole('heading', { name: 'Log in to your account' })).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Email address' })).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Password' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Log in', exact: true })).toBeVisible();
});

test('a seeded user can sign in and reach the dashboard', async ({ page }) => {
    await logIn(page, 'owner@keel.test');

    await expect(page).not.toHaveURL(/\/login/);
    await expect(page.getByRole('link', { name: /log in/i })).toHaveCount(0);
});

test('bad credentials are rejected without leaving the form', async ({ page }) => {
    await logIn(page, 'owner@keel.test', 'wrong-password');

    await expect(page).toHaveURL(/\/login/);
});
