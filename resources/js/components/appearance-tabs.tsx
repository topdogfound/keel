import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { HTMLAttributes, MouseEvent } from 'react';
import type { Appearance } from '@/hooks/use-appearance';
import { resolveAppearance, useAppearance } from '@/hooks/use-appearance';
import { startThemeTransition } from '@/lib/theme-transition';
import { cn } from '@/lib/utils';

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, resolvedAppearance, updateAppearance } =
        useAppearance();

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: 'Light' },
        { value: 'dark', icon: Moon, label: 'Dark' },
        { value: 'system', icon: Monitor, label: 'System' },
    ];

    const handleSelect = (
        event: MouseEvent<HTMLButtonElement>,
        value: Appearance,
    ): void => {
        // Nothing on screen changes when re-picking the active tab, or when
        // `system` already resolves to the current theme -- reveal would just
        // wipe the same colours over themselves.
        if (resolveAppearance(value) === resolvedAppearance) {
            updateAppearance(value);

            return;
        }

        startThemeTransition(() => updateAppearance(value), {
            origin: event.currentTarget,
        });
    };

    return (
        <div
            className={cn(
                'inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800',
                className,
            )}
            {...props}
        >
            {tabs.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    onClick={(event) => handleSelect(event, value)}
                    className={cn(
                        'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                        appearance === value
                            ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                            : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                    )}
                >
                    <Icon className="-ml-1 h-4 w-4" />
                    <span className="ml-1.5 text-sm">{label}</span>
                </button>
            ))}
        </div>
    );
}
