<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Home, BookOpen, History, User, Bell } from 'lucide-vue-next';
import { computed } from 'vue';
import { dashboard } from '@/routes/campus';
import { index as classesIndex } from '@/routes/classroom';
import type { AppPageProps } from '@/types';

const page = usePage<AppPageProps>();
const currentCampus = computed(() => page.props.currentCampus?.slug);

const navItems = computed(() => [
    {
        label: 'Dashboard',
        icon: Home,
        href: currentCampus.value ? dashboard({ campus: currentCampus.value }).url : '#',
        active: page.url?.includes('dashboard'),
    },
    {
        label: 'Classes',
        icon: BookOpen,
        href: currentCampus.value ? classesIndex({ campus: currentCampus.value }).url : '#',
        active: page.url?.includes('classroom'),
    },
    {
        label: 'History',
        icon: History,
        href: '#',
        active: page.url?.includes('history'),
    },
    {
        label: 'Profile',
        icon: User,
        href: '#',
        active: page.url?.includes('profile'),
    },
]);
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-50 border-t border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 md:hidden">
        <div class="flex h-16 items-center justify-around px-2">
            <Link
                v-for="item in navItems"
                :key="item.label"
                :href="item.href"
                class="flex flex-col items-center justify-center gap-1 rounded-lg px-3 py-2 transition-colors min-w-[64px]"
                :class="item.active ? 'text-primary' : 'text-muted-foreground hover:text-foreground'"
            >
                <component :is="item.icon" class="h-5 w-5" />
                <span class="text-[10px] font-medium">{{ item.label }}</span>
            </Link>
        </div>
    </nav>
</template>
