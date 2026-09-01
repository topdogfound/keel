import type { Page } from '@playwright/test';

/**
 * Sign in through the real login form.
 *
 * Selectors are deliberately role-based and exact: the form has a "Show
 * password" toggle and a passkey button, so loose regexes match more than one
 * element and fail on strict mode rather than doing what you meant.
 */
export async function logIn(page: Page, email: string, password = 'password') {
    await page.goto('/login');
    await page.getByRole('textbox', { name: 'Email address' }).fill(email);
    await page.getByRole('textbox', { name: 'Password' }).fill(password);
    await page.getByRole('button', { name: 'Log in', exact: true }).click();
}
