export * from './auth';
export * from './navigation';
export * from './ui';

import type { Auth } from './auth';

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    academic?: {
        enabledModules: string[];
        person: Record<string, unknown> | null;
    };
    sidebarOpen: boolean;
    authLayout: 'simple' | 'card' | 'split';
    [key: string]: unknown;
};

export type SharedData = AppPageProps;
