<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    ClipboardList,
    Folder,
    GraduationCap,
    History,
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
import { dashboard } from '@/routes/campus';
import { dashboard as dashboardRedirect } from '@/routes';
import { index as applicationsIndex } from '@/routes/applications';
import { index as classroomIndex } from '@/routes/classroom';
import { show as academicHistory } from '@/routes/academic-history';
import { type NavItem } from '@/types';
import type { AppPageProps } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage<AppPageProps>();
const currentCampus = page.props.currentCampus;
const roles = page.props.auth.roles ?? [];
const hasAnyRole = (...allowedRoles: string[]) =>
    roles.some((role) => allowedRoles.includes(role));

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: currentCampus
            ? dashboard({ campus: currentCampus.slug })
            : dashboardRedirect(),
        icon: LayoutGrid,
    },
];

if (
    currentCampus &&
    page.props.academic?.enabledModules.includes('admissions') &&
    hasAnyRole('applicant', 'super_admin', 'school_admin', 'admissions_officer')
) {
    mainNavItems.push({
        title: 'Applications',
        href: applicationsIndex({ campus: currentCampus!.slug }),
        icon: ClipboardList,
    });
}

if (
    currentCampus &&
    page.props.academic?.enabledModules.includes('enrollment') &&
    hasAnyRole('student')
) {
    mainNavItems.push({
        title: 'Academic History',
        href: academicHistory({ campus: currentCampus!.slug }),
        icon: History,
    });
}

if (
    currentCampus &&
    page.props.academic?.enabledModules.includes('classroom') &&
    hasAnyRole('teacher', 'student', 'super_admin', 'school_admin')
) {
    mainNavItems.push({
        title: 'My Classes',
        href: classroomIndex({ campus: currentCampus!.slug }),
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
                        <Link
                            :href="
                                currentCampus
                                    ? dashboard({
                                          campus: currentCampus.slug,
                                      })
                                    : dashboardRedirect()
                            "
                        >
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
