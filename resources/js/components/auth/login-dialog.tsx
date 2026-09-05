import { usePage } from '@inertiajs/react';
import { createContext, useContext, useMemo, useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { LoginForm } from '@/components/auth/login-form';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Auth, LoginChallenge } from '@/types';

type LoginDialogContextValue = {
    openLogin: () => void;
};

const LoginDialogContext = createContext<LoginDialogContextValue | null>(null);

export function LoginDialogProvider({
    children,
}: {
    children: React.ReactNode;
}) {
    const page = usePage<{
        auth: Auth;
        loginChallenge: LoginChallenge;
        openLoginModal: boolean;
    }>();
    const { auth, loginChallenge, openLoginModal } = page.props;
    const [open, setOpen] = useState(openLoginModal);

    const value = useMemo<LoginDialogContextValue>(
        () => ({
            openLogin: () => setOpen(true),
        }),
        [],
    );

    return (
        <LoginDialogContext.Provider value={value}>
            {children}
            <Dialog open={open && !auth.user} onOpenChange={setOpen}>
                <DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto p-6 sm:max-w-md sm:p-8">
                    <div className="mb-1 flex justify-center">
                        <div className="flex h-9 w-9 items-center justify-center rounded-md">
                            <AppLogoIcon className="size-9 fill-current text-[var(--foreground)] dark:text-white" />
                        </div>
                    </div>
                    <DialogHeader className="pr-6 text-center">
                        <DialogTitle className="text-xl">
                            {loginChallenge ? 'Verify your login' : 'Sign in'}
                        </DialogTitle>
                        <DialogDescription>
                            {loginChallenge
                                ? `Enter the code sent to ${loginChallenge.maskedDestination}.`
                                : 'Use a one-time code sent to your email to continue.'}
                        </DialogDescription>
                    </DialogHeader>
                    <LoginForm
                        challenge={loginChallenge}
                        modal
                        returnUrl={page.url}
                    />
                </DialogContent>
            </Dialog>
        </LoginDialogContext.Provider>
    );
}

export function useLoginDialog(): LoginDialogContextValue {
    const context = useContext(LoginDialogContext);

    if (!context) {
        throw new Error(
            'useLoginDialog must be used inside LoginDialogProvider.',
        );
    }

    return context;
}
