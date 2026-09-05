import { useSyncExternalStore } from 'react';

function subscribe(callback: () => void): () => void {
    const id = setInterval(callback, 1000);
    return () => clearInterval(id);
}

/**
 * Ticks once a second so the "Resend code" button's disabled state and
 * countdown label stay in sync with a server-provided timestamp, without a
 * timer that drifts (each render recomputes from Date.now(), not from a
 * decrementing counter).
 */
export function useResendCountdown(resendAvailableAt?: string): {
    secondsRemaining: number;
    active: boolean;
} {
    useSyncExternalStore(
        subscribe,
        () => Math.floor(Date.now() / 1000),
        () => 0,
    );

    if (!resendAvailableAt) {
        return { secondsRemaining: 0, active: false };
    }

    const secondsRemaining = Math.max(
        0,
        Math.ceil((new Date(resendAvailableAt).getTime() - Date.now()) / 1000),
    );

    return { secondsRemaining, active: secondsRemaining > 0 };
}
