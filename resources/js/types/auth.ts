export type Gender = 'male' | 'female' | 'prefer_not_to_say';

export type User = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    gender?: Gender | null;
    avatar_url?: string | null;
    google_linked?: boolean;
    github_linked?: boolean;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    /**
     * Null for guests. The home page is public, so every component reachable
     * from the app layout has to handle a visitor who is not signed in.
     */
    user: User | null;
};

export type LoginSocialProvider = {
    key: 'google' | 'github';
    label: string;
    enabled: boolean;
};

export type LoginConfiguration = {
    otpLength: number;
    socialProviders: LoginSocialProvider[];
};

export type LoginChallenge = {
    maskedDestination: string;
    resendAvailableAt: string;
} | null;
