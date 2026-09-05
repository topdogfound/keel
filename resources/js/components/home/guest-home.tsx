import { Link, usePage } from '@inertiajs/react';
import { Container, KeyRound, ShieldCheck } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { login } from '@/routes';

const highlights = [
    {
        title: 'Docker-first',
        description:
            'The whole stack runs in containers, so every environment is byte-identical.',
        icon: Container,
    },
    {
        title: 'Secure by default',
        description:
            'Passkeys, two-factor authentication and role-based access ship in the box.',
        icon: KeyRound,
    },
    {
        title: 'Observable',
        description:
            'Health checks, queue monitoring and request tracing are wired up from day one.',
        icon: ShieldCheck,
    },
];

export default function GuestHome() {
    const { name } = usePage().props;

    return (
        <div className="flex flex-1 flex-col items-center justify-center px-4 py-16 sm:py-24">
            <div className="flex w-full max-w-3xl flex-col items-center text-center">
                <div className="bg-sidebar-primary text-sidebar-primary-foreground flex size-14 items-center justify-center rounded-2xl">
                    <AppLogoIcon className="size-8 fill-current text-white dark:text-black" />
                </div>

                <h1 className="text-foreground mt-8 text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                    {name}
                </h1>

                <p className="text-muted-foreground mt-4 max-w-xl text-base text-pretty sm:text-lg">
                    The structural backbone your application is built on. Sign
                    in to pick up where you left off.
                </p>

                <Button size="lg" className="mt-8" asChild>
                    <Link href={login()}>Log in</Link>
                </Button>
            </div>

            <div className="mt-16 grid w-full max-w-4xl gap-4 sm:grid-cols-3">
                {highlights.map((highlight) => (
                    <div
                        key={highlight.title}
                        className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-5 text-left"
                    >
                        <highlight.icon className="text-muted-foreground size-5" />
                        <h2 className="text-foreground mt-3 text-sm font-medium">
                            {highlight.title}
                        </h2>
                        <p className="text-muted-foreground mt-1 text-sm text-pretty">
                            {highlight.description}
                        </p>
                    </div>
                ))}
            </div>
        </div>
    );
}
