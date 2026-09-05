import { Form, router, usePage } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { RotateCcw } from 'lucide-react';
import { useState } from 'react';
import GithubIcon from '@/components/github-icon';
import GoogleIcon from '@/components/google-icon';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useResendCountdown } from '@/hooks/use-resend-countdown';
import login from '@/routes/login';
import type { LoginChallenge, LoginConfiguration } from '@/types';

const providerIcons = {
    google: GoogleIcon,
    github: GithubIcon,
} as const;

export function LoginForm({
    challenge,
    modal = false,
    returnUrl,
}: {
    challenge: LoginChallenge;
    modal?: boolean;
    returnUrl?: string;
}) {
    const { loginConfiguration, errors: pageErrors } = usePage<{
        loginConfiguration: LoginConfiguration;
    }>().props;
    const [emailCode, setEmailCode] = useState('');
    const enabledProviders = loginConfiguration.socialProviders.filter(
        (provider) => provider.enabled,
    );

    const {
        secondsRemaining: resendSecondsRemaining,
        active: resendCooldownActive,
    } = useResendCountdown(challenge?.resendAvailableAt);

    if (challenge) {
        return (
            <Form {...login.verify.form()} className="flex flex-col gap-6">
                {({ processing, errors }) => {
                    const hasCodeError = Boolean(errors.email_code);

                    return (
                        <div className="grid gap-6">
                            {modal && (
                                <input type="hidden" name="modal" value="1" />
                            )}
                            <div className="grid gap-3">
                                <div className="flex items-center justify-between gap-3">
                                    <Label className="text-sm font-medium">
                                        Verification code
                                    </Label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.post(
                                                login.resend(),
                                                { modal },
                                                { preserveScroll: true },
                                            )
                                        }
                                        disabled={
                                            processing || resendCooldownActive
                                        }
                                    >
                                        <RotateCcw className="size-3.5" />
                                        {resendCooldownActive
                                            ? `Resend in ${resendSecondsRemaining}s`
                                            : 'Resend code'}
                                    </Button>
                                </div>
                                {pageErrors.resend && (
                                    <p className="text-destructive text-sm">
                                        {pageErrors.resend}
                                    </p>
                                )}
                                <div className="flex justify-center">
                                    <InputOTP
                                        name="email_code"
                                        maxLength={loginConfiguration.otpLength}
                                        pattern={REGEXP_ONLY_DIGITS}
                                        value={emailCode}
                                        onChange={setEmailCode}
                                        disabled={processing}
                                        autoFocus
                                        aria-invalid={hasCodeError}
                                    >
                                        <InputOTPGroup>
                                            {Array.from(
                                                {
                                                    length: loginConfiguration.otpLength,
                                                },
                                                (_, index) => (
                                                    <InputOTPSlot
                                                        key={index}
                                                        index={index}
                                                        className={
                                                            hasCodeError
                                                                ? 'border-destructive'
                                                                : undefined
                                                        }
                                                    />
                                                ),
                                            )}
                                        </InputOTPGroup>
                                    </InputOTP>
                                </div>
                                {errors.email_code && (
                                    <p className="text-destructive text-sm">
                                        {errors.email_code}
                                    </p>
                                )}
                            </div>
                            <div className="text-left">
                                <TextLink
                                    href={login.challenge.destroy()}
                                    method="delete"
                                    data={{ modal }}
                                    as="button"
                                    type="button"
                                    className="text-sm"
                                >
                                    Use a different email
                                </TextLink>
                            </div>
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                                data-test="login-verify-button"
                            >
                                {processing && <Spinner />}
                                Verify and sign in
                            </Button>
                        </div>
                    );
                }}
            </Form>
        );
    }

    return (
        <div className="flex flex-col gap-6">
            <Form {...login.store.form()} className="flex flex-col gap-6">
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        {modal && (
                            <input type="hidden" name="modal" value="1" />
                        )}
                        {modal && returnUrl && (
                            <input
                                type="hidden"
                                name="return_url"
                                value={returnUrl}
                            />
                        )}
                        <div className="grid gap-2">
                            <Label htmlFor={modal ? 'modal-email' : 'email'}>
                                Email address
                            </Label>
                            <Input
                                id={modal ? 'modal-email' : 'email'}
                                type="email"
                                name="email"
                                required
                                autoFocus
                                autoComplete="email"
                                placeholder="email@example.com"
                                aria-invalid={Boolean(errors.email)}
                            />
                            {errors.email && (
                                <p className="text-destructive text-sm">
                                    {errors.email}
                                </p>
                            )}
                        </div>
                        <div className="flex items-center gap-3">
                            <Checkbox
                                id={modal ? 'modal-remember' : 'remember'}
                                name="remember"
                            />
                            <Label
                                htmlFor={modal ? 'modal-remember' : 'remember'}
                            >
                                Remember me
                            </Label>
                        </div>
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing && <Spinner />}
                            Send code
                        </Button>
                    </div>
                )}
            </Form>

            {enabledProviders.length > 0 && (
                <div className="grid gap-4">
                    <div className="relative">
                        <div className="absolute inset-0 flex items-center">
                            <span className="w-full border-t" />
                        </div>
                        <div className="relative flex justify-center text-xs uppercase">
                            <span className="bg-background text-muted-foreground px-2">
                                or
                            </span>
                        </div>
                    </div>
                    {enabledProviders.map((provider) => {
                        const Icon = providerIcons[provider.key];
                        const redirect = login.social.redirect(
                            { provider: provider.key },
                            modal && returnUrl
                                ? { query: { return_url: returnUrl } }
                                : undefined,
                        );

                        return (
                            <Button
                                key={provider.key}
                                variant="outline"
                                className="w-full"
                                asChild
                            >
                                <a
                                    href={redirect.url}
                                    data-test={`${provider.key}-login-button`}
                                >
                                    <Icon className="size-4" />
                                    Continue with {provider.label}
                                </a>
                            </Button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
