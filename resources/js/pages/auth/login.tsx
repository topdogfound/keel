import { Head, setLayoutProps } from '@inertiajs/react';
import { LoginForm } from '@/components/auth/login-form';
import type { LoginChallenge } from '@/types';

export default function Login({
    challenge,
    status,
}: {
    challenge: LoginChallenge;
    status?: string;
}) {
    const title = challenge ? 'Verify your login' : 'Sign in with email';
    const description = challenge
        ? `Enter the verification code we sent to ${challenge.maskedDestination}.`
        : 'Use a one-time passcode sent to your email to sign in.';

    setLayoutProps({ title, description });

    return (
        <>
            <Head title="Log in" />
            <div className="space-y-6">
                {status && (
                    <div className="text-center text-sm font-medium text-green-600">
                        {status}
                    </div>
                )}
                <LoginForm challenge={challenge} />
            </div>
        </>
    );
}
