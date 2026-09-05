import { Form, usePage } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useState } from 'react';
import EmailController from '@/actions/App/Http/Controllers/Settings/EmailController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { LoginConfiguration } from '@/types';

/**
 * Two steps, like the login OTP flow: request a code sent to the *new*
 * address, then confirm it. The email only changes once the code verifies.
 */
export function EmailChangeForm({ currentEmail }: { currentEmail: string }) {
    const { loginConfiguration } = usePage<{
        loginConfiguration: LoginConfiguration;
    }>().props;
    const [pendingEmail, setPendingEmail] = useState<string | null>(null);
    const [newEmail, setNewEmail] = useState('');
    const [code, setCode] = useState('');

    if (pendingEmail) {
        return (
            <Form
                {...EmailController.update.form()}
                resetOnSuccess
                onSuccess={() => setPendingEmail(null)}
                className="space-y-3"
            >
                {({ processing, errors }) => (
                    <>
                        <p className="text-muted-foreground text-sm">
                            Enter the code sent to {pendingEmail}.
                        </p>
                        <InputOTP
                            name="email_code"
                            maxLength={loginConfiguration.otpLength}
                            pattern={REGEXP_ONLY_DIGITS}
                            value={code}
                            onChange={setCode}
                            disabled={processing}
                            autoFocus
                        >
                            <InputOTPGroup>
                                {Array.from(
                                    { length: loginConfiguration.otpLength },
                                    (_, index) => (
                                        <InputOTPSlot
                                            key={index}
                                            index={index}
                                        />
                                    ),
                                )}
                            </InputOTPGroup>
                        </InputOTP>
                        <InputError message={errors.email_code} />
                        <div className="flex items-center gap-2">
                            <Button
                                type="submit"
                                disabled={processing}
                                data-test="confirm-email-change-button"
                            >
                                {processing && <Spinner />}
                                Confirm
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => setPendingEmail(null)}
                            >
                                Cancel
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        );
    }

    return (
        <Form
            {...EmailController.store.form()}
            resetOnSuccess={false}
            onSuccess={() => setPendingEmail(newEmail)}
            className="space-y-3"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="new-email">New email address</Label>
                        <Input
                            id="new-email"
                            type="email"
                            name="email"
                            value={newEmail}
                            onChange={(event) =>
                                setNewEmail(event.target.value)
                            }
                            placeholder={currentEmail}
                            autoComplete="off"
                            required
                        />
                        <InputError message={errors.email} />
                    </div>
                    <Button
                        type="submit"
                        disabled={processing || newEmail === ''}
                        data-test="send-email-change-code-button"
                    >
                        {processing && <Spinner />}
                        Send verification code
                    </Button>
                </>
            )}
        </Form>
    );
}
