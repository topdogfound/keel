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
    await expect(
        page.getByRole('heading', { name: 'Sign in with email' }),
    ).toBeVisible();
    await expect(
        page.getByRole('textbox', { name: 'Email address' }),
    ).toBeVisible();
    await expect(page.getByRole('button', { name: 'Send code' })).toBeVisible();
});

test('a seeded user can sign in and reach the home page', async ({ page }) => {
    await logIn(page, 'member@keel.test');

    await expect(page).not.toHaveURL(/\/login/);
    await expect(page.getByRole('button', { name: /log in/i })).toHaveCount(0);
});

test('an invalid code is rejected without signing in', async ({ page }) => {
    await page.goto('/login');
    await page
        .getByRole('textbox', { name: 'Email address' })
        .fill('member@keel.test');
    await page.getByRole('button', { name: 'Send code' }).click();

    await expect(
        page.getByText('Verification code', { exact: true }),
    ).toBeVisible();
    await page.locator('input[name="email_code"]').fill('000000');
    await page.getByRole('button', { name: 'Verify and sign in' }).click();

    await expect(page).toHaveURL(/\/login/);
    await expect(
        page.getByText('The verification code is invalid.'),
    ).toBeVisible();
});

test('the login modal signs in without leaving the page', async ({ page }) => {
    await page.goto('/');

    await page.getByRole('button', { name: 'Log in', exact: true }).click();
    await expect(page.getByRole('dialog', { name: 'Sign in' })).toBeVisible();

    await page
        .getByRole('textbox', { name: 'Email address' })
        .fill('member@keel.test');
    await page.getByRole('button', { name: 'Send code' }).click();

    await expect(
        page.getByText('Verification code', { exact: true }),
    ).toBeVisible();

    const response = await page.request.get(
        '/_testing/login-otp?email=member%40keel.test',
    );
    const { code } = (await response.json()) as { code: string | null };

    await page.locator('input[name="email_code"]').fill(code ?? '');
    await page.getByRole('button', { name: 'Verify and sign in' }).click();

    await expect(page).toHaveURL('/');
    await expect(page.getByRole('dialog')).toHaveCount(0);
    await expect(page.getByRole('button', { name: /log in/i })).toHaveCount(0);
});
