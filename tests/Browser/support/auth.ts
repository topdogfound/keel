import { expect, type Page } from '@playwright/test';

/**
 * Sign in through the real email-OTP flow. There's no password: this reads
 * the plaintext code from a local/testing-only debug endpoint (see
 * OtpDebugController) instead of parsing a mailbox. `page.request` shares
 * the browser context's cookies, so the debug request lines up with the
 * session that just requested the code.
 */
export async function logIn(page: Page, email: string) {
    await page.goto('/login');
    await page.getByRole('textbox', { name: 'Email address' }).fill(email);
    await page.getByRole('button', { name: 'Send code' }).click();

    await expect(
        page.getByText('Verification code', { exact: true }),
    ).toBeVisible();

    const response = await page.request.get(
        `/_testing/login-otp?email=${encodeURIComponent(email)}`,
    );
    const { code } = (await response.json()) as { code: string | null };

    await page.locator('input[name="email_code"]').fill(code ?? '');
    await page.getByRole('button', { name: 'Verify and sign in' }).click();
}
