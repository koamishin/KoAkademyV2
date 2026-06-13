<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    ClipboardList,
    Folder,
    GraduationCap,
    LayoutGrid,
} from 'lucide-vue-next';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as applicationsIndex } from '@/routes/applications';
import { index as classroomIndex } from '@/routes/classroom';
import { type NavItem } from '@/types';
import type { AppPageProps } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage<AppPageProps>();
const roles = page.props.auth.roles ?? [];
const hasAnyRole = (...allowedRoles: string[]) =>
    roles.some((role) => allowedRoles.includes(role));

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

if (
    page.props.academic?.enabledModules.includes('admissions') &&
    hasAnyRole('applicant', 'super_admin', 'school_admin', 'admissions_officer')
) {
    mainNavItems.push({
        title: 'Applications',
        href: applicationsIndex(),
        icon: ClipboardList,
    });
}

if (
    page.props.academic?.enabledModules.includes('classroom') &&
    hasAnyRole('teacher', 'student', 'super_admin', 'school_admin')
) {
    mainNavItems.push({
        title: 'My Classes',
        href: classroomIndex(),
        icon: GraduationCap,
    });
}

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
