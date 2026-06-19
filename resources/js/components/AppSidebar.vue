<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    ClipboardCheck,
    ClipboardList,
    ExternalLink,
    GraduationCap,
    History,
    LayoutGrid,
    ListChecks,
    Palette,
    School,
    ShieldCheck,
    UserCog,
    UsersRound,
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
import { dashboard as dashboardRedirect } from '@/routes';
import { edit as appearanceEdit } from '@/routes/appearance';
import { type NavGroup, type NavItem } from '@/types';
import type { AppPageProps } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage<AppPageProps>();

const iconMap = {
    Bell,
    ClipboardCheck,
    ClipboardList,
    ExternalLink,
    GraduationCap,
    History,
    LayoutGrid,
    ListChecks,
    School,
    ShieldCheck,
    UserCog,
    UsersRound,
};

const portalNavigation: NavGroup[] = (page.props.portal?.navigation ?? []).map(
    (group) => ({
        ...group,
        items: group.items.map((item) => ({
            ...item,
            icon:
                typeof item.icon === 'string'
                    ? iconMap[item.icon as keyof typeof iconMap]
                    : item.icon,
        })),
    }),
);

const footerNavItems: NavItem[] = [
    {
        title: 'Appearance',
        href: appearanceEdit(),
        icon: Palette,
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
                                page.props.portal?.home ?? dashboardRedirect()
                            "
                        >
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="portalNavigation" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
