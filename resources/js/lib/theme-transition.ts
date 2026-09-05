import { flushSync } from 'react-dom';

/**
 * The clip-path reveal that plays while the theme flips, lifted from MagicUI's
 * `@magicui/animated-theme-toggler`. Upstream welds this to its own Sun/Moon
 * button that owns a `theme` key in localStorage; this app already has an
 * appearance store (`@/hooks/use-appearance`) and a three-way Light/Dark/System
 * control, so only the animation is kept and the caller supplies the change.
 */
export type TransitionVariant =
    | 'circle'
    | 'square'
    | 'triangle'
    | 'diamond'
    | 'hexagon'
    | 'rectangle'
    | 'star';

export type ThemeTransitionOptions = {
    /** Element the reveal expands from. Falls back to the viewport centre. */
    readonly origin?: HTMLElement | null;
    readonly variant?: TransitionVariant;
    readonly duration?: number;
};

const DEFAULT_VARIANT: TransitionVariant = 'circle';
const DEFAULT_DURATION = 400;

function polygonCollapsed(point: string, vertexCount: number): string {
    return `polygon(${Array.from({ length: vertexCount }, () => point).join(', ')})`;
}

/**
 * All coordinates are percentages of the snapshot reference box: Chrome 150
 * renders absolute px clip-path coordinates on ::view-transition-new(root)
 * unscaled on fractional display scales (e.g. Windows 150%) for the first
 * transition after load, so px values land at the wrong position.
 */
function getThemeTransitionClipPaths(
    variant: TransitionVariant,
    cx: number,
    cy: number,
    maxRadius: number,
    viewportWidth: number,
    viewportHeight: number,
): [string, string] {
    const toX = (x: number) => `${(x / viewportWidth) * 100}%`;
    const toY = (y: number) => `${(y / viewportHeight) * 100}%`;
    const point = (x: number, y: number) => `${toX(x)} ${toY(y)}`;

    // circle() percentage radii resolve against hypot(w, h) / sqrt(2) of the
    // reference box.
    const toRadius = (r: number) =>
        `${(r / (Math.hypot(viewportWidth, viewportHeight) / Math.SQRT2)) * 100}%`;

    switch (variant) {
        case 'square': {
            const halfW = Math.max(cx, viewportWidth - cx);
            const halfH = Math.max(cy, viewportHeight - cy);
            const halfSide = Math.max(halfW, halfH) * 1.05;
            const end = [
                point(cx - halfSide, cy - halfSide),
                point(cx + halfSide, cy - halfSide),
                point(cx + halfSide, cy + halfSide),
                point(cx - halfSide, cy + halfSide),
            ].join(', ');

            return [polygonCollapsed(point(cx, cy), 4), `polygon(${end})`];
        }
        case 'triangle': {
            const scale = maxRadius * 2.2;
            const dx = (Math.sqrt(3) / 2) * scale;
            const verts = [
                point(cx, cy - scale),
                point(cx + dx, cy + 0.5 * scale),
                point(cx - dx, cy + 0.5 * scale),
            ].join(', ');

            return [polygonCollapsed(point(cx, cy), 3), `polygon(${verts})`];
        }
        case 'diamond': {
            // Slightly larger than the view-transition circle radius so
            // axis-aligned coverage matches the circle reveal.
            const R = maxRadius * Math.SQRT2;
            const end = [
                point(cx, cy - R),
                point(cx + R, cy),
                point(cx, cy + R),
                point(cx - R, cy),
            ].join(', ');

            return [polygonCollapsed(point(cx, cy), 4), `polygon(${end})`];
        }
        case 'hexagon': {
            const R = maxRadius * Math.SQRT2;
            const verts: string[] = [];

            for (let i = 0; i < 6; i++) {
                const a = -Math.PI / 2 + (i * Math.PI) / 3;

                verts.push(point(cx + R * Math.cos(a), cy + R * Math.sin(a)));
            }

            return [
                polygonCollapsed(point(cx, cy), 6),
                `polygon(${verts.join(', ')})`,
            ];
        }
        case 'rectangle': {
            const halfW = Math.max(cx, viewportWidth - cx);
            const halfH = Math.max(cy, viewportHeight - cy);
            const end = [
                point(cx - halfW, cy - halfH),
                point(cx + halfW, cy - halfH),
                point(cx + halfW, cy + halfH),
                point(cx - halfW, cy + halfH),
            ].join(', ');

            return [polygonCollapsed(point(cx, cy), 4), `polygon(${end})`];
        }
        case 'star': {
            // Small overscan so the last frames never leave a 1px seam before
            // the transition group ends.
            const R = maxRadius * Math.SQRT2 * 1.03;
            const innerRatio = 0.42;
            const starPolygon = (radius: number) => {
                const verts: string[] = [];

                for (let i = 0; i < 5; i++) {
                    const outerA = -Math.PI / 2 + (i * 2 * Math.PI) / 5;

                    verts.push(
                        point(
                            cx + radius * Math.cos(outerA),
                            cy + radius * Math.sin(outerA),
                        ),
                    );

                    const innerA = outerA + Math.PI / 5;

                    verts.push(
                        point(
                            cx + radius * innerRatio * Math.cos(innerA),
                            cy + radius * innerRatio * Math.sin(innerA),
                        ),
                    );
                }

                return `polygon(${verts.join(', ')})`;
            };

            return [starPolygon(Math.max(2, R * 0.025)), starPolygon(R)];
        }
        case 'circle':
        default:
            return [
                `circle(0% at ${point(cx, cy)})`,
                `circle(${toRadius(maxRadius)} at ${point(cx, cy)})`,
            ];
    }
}

const prefersReducedMotion = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
};

const canAnimate = (): boolean => {
    if (typeof document === 'undefined') {
        return false;
    }

    return (
        typeof document.startViewTransition === 'function' &&
        // A reveal that paints over the whole viewport is exactly the kind of
        // motion people disable; upstream ships no such guard.
        !prefersReducedMotion() &&
        // Re-entry guard: the flag lives on <html> rather than in a ref so a
        // second click mid-reveal is ignored no matter which control fired it.
        document.documentElement.dataset.themeVt !== 'active'
    );
};

/**
 * Applies `apply` inside a view transition, revealing the result with an
 * expanding clip-path. Falls back to applying the change directly when the
 * browser has no View Transitions API, when the user prefers reduced motion,
 * or while another reveal is still running.
 */
export function startThemeTransition(
    apply: () => void,
    options: ThemeTransitionOptions = {},
): void {
    if (!canAnimate()) {
        apply();

        return;
    }

    const {
        origin,
        variant = DEFAULT_VARIANT,
        duration = DEFAULT_DURATION,
    } = options;

    // innerWidth/innerHeight (not visualViewport): percentages must resolve
    // against the snapshot reference box, which includes classic scrollbars.
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    let x = viewportWidth / 2;
    let y = viewportHeight / 2;

    if (origin) {
        const { top, left, width, height } = origin.getBoundingClientRect();

        x = left + width / 2;
        y = top + height / 2;
    }

    const maxRadius = Math.hypot(
        Math.max(x, viewportWidth - x),
        Math.max(y, viewportHeight - y),
    );

    const clipPath = getThemeTransitionClipPaths(
        variant,
        x,
        y,
        maxRadius,
        viewportWidth,
        viewportHeight,
    );

    const root = document.documentElement;

    root.dataset.themeVt = 'active';
    root.style.setProperty('--theme-vt-duration', `${duration}ms`);
    root.style.setProperty('--theme-vt-clip-from', clipPath[0]);

    let animation: Animation | null = null;

    const cleanup = (): void => {
        animation?.cancel();
        animation = null;

        delete root.dataset.themeVt;
        root.style.removeProperty('--theme-vt-duration');
        root.style.removeProperty('--theme-vt-clip-from');
    };

    // flushSync so React commits the caller's state change inside the callback,
    // otherwise the new snapshot still shows the pre-toggle UI.
    const transition = document.startViewTransition(() => flushSync(apply));

    transition.finished.finally(cleanup).catch(() => {});

    transition.ready
        .then(() => {
            animation = root.animate(
                { clipPath },
                {
                    duration,
                    // Star: linear avoids easing overshoot that fights polygon
                    // interpolation at t -> 1.
                    easing: variant === 'star' ? 'linear' : 'ease-in-out',
                    fill: 'forwards',
                    pseudoElement: '::view-transition-new(root)',
                },
            );
        })
        .catch(() => {});
}
