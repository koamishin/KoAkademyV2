export * from './auth';
export * from './navigation';
export * from './ui';

import type { Auth } from './auth';
import type { NavGroup } from './navigation';

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    academic?: {
        enabledModules: string[];
        person: Record<string, unknown> | null;
    };
    portal: {
        role: 'admin' | 'faculty' | 'student' | 'applicant' | 'guardian' | 'unknown';
        home: string;
        navigation: NavGroup[];
        canAccessAdminPortal: boolean;
    };
    currentCampus: {
        id: number;
        name: string;
        code: string;
        slug: string;
    } | null;
    sidebarOpen: boolean;
    authLayout: 'simple' | 'card' | 'split';
    [key: string]: unknown;
};

export type SharedData = AppPageProps;
