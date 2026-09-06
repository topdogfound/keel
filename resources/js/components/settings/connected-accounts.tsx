import { Form, usePage } from '@inertiajs/react';
import { type ComponentType, type SVGAttributes } from 'react';
import connections from '@/routes/settings/connections';
import GithubIcon from '@/components/github-icon';
import GoogleIcon from '@/components/google-icon';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { LoginConfiguration, User } from '@/types';

const providerIcons: Record<
    'google' | 'github',
    ComponentType<SVGAttributes<SVGElement>>
> = {
    google: GoogleIcon,
    github: GithubIcon,
};

export function ConnectedAccounts({ user }: { user: User }) {
    const { loginConfiguration } = usePage<{
        loginConfiguration: LoginConfiguration;
    }>().props;

    const linked: Record<'google' | 'github', boolean> = {
        google: Boolean(user.google_linked),
        github: Boolean(user.github_linked),
    };

    // Enabled providers only. A disabled provider's connect and disconnect
    // routes both 404 (SocialConnectionController::abortUnlessAvailable), so
    // listing one -- even for someone who linked it before it was turned off
    // -- would render a control that cannot work.
    const availableProviders = loginConfiguration.socialProviders.filter(
        (provider) => provider.enabled,
    );

    if (availableProviders.length === 0) {
        return null;
    }

    return (
        <div className="space-y-4">
            <Heading
                variant="small"
                title="Connected accounts"
                description="Sign-in methods linked to this account"
            />

            <ul className="divide-border divide-y rounded-lg border">
                {availableProviders.map((provider) => {
                    const Icon = providerIcons[provider.key];
                    const isLinked = linked[provider.key];

                    return (
                        <li
                            key={provider.key}
                            className="flex items-center justify-between gap-4 p-4"
                        >
                            <div className="flex items-center gap-3">
                                <Icon className="size-5 shrink-0" />
                                <div className="grid gap-0.5">
                                    <span className="text-sm font-medium">
                                        {provider.label}
                                    </span>
                                    <Badge
                                        variant={
                                            isLinked ? 'secondary' : 'outline'
                                        }
                                        className="w-fit"
                                    >
                                        {isLinked
                                            ? 'Connected'
                                            : 'Not connected'}
                                    </Badge>
                                </div>
                            </div>

                            {isLinked ? (
                                <Form
                                    {...connections.destroy.form({
                                        provider: provider.key,
                                    })}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            size="sm"
                                            disabled={processing}
                                            data-test={`disconnect-${provider.key}-button`}
                                        >
                                            {processing && <Spinner />}
                                            Disconnect
                                        </Button>
                                    )}
                                </Form>
                            ) : (
                                <Button variant="outline" size="sm" asChild>
                                    <a
                                        href={
                                            connections.redirect({
                                                provider: provider.key,
                                            }).url
                                        }
                                        data-test={`connect-${provider.key}-button`}
                                    >
                                        Connect
                                    </a>
                                </Button>
                            )}
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
