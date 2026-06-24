<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Lenis from 'lenis';
import {
    ArrowRight,
    BarChart3,
    BookOpen,
    Briefcase,
    Calendar,
    CheckCircle2,
    ChevronDown,
    ChevronRight,
    Database,
    Globe,
    GraduationCap,
    MessageSquare,
    ShieldCheck,
    Smartphone,
    Users,
    Zap,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import ImpersonateBanner from '@/components/ImpersonateBanner.vue';
import KoamishinLogo from '@/components/KoamishinLogo.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    canRegister?: boolean;
    laravelVersion?: string;
    phpVersion?: string;
}>();

type RouteParameters =
    | string
    | number
    | Array<string | number>
    | Record<string, unknown>
    | undefined;

type RouteFunction = (
    name: string,
    params?: RouteParameters,
    absolute?: boolean,
) => string;

const route = ((globalThis as typeof globalThis & { route?: RouteFunction })
    .route ?? ((name: string) => name)) as RouteFunction;

const navItems = [
    { id: 'programs', label: 'Capabilities' },
    { id: 'impact', label: 'Impact' },
    { id: 'why', label: 'Why Koamishin' },
    { id: 'faq', label: 'Support' },
    { id: 'contact', label: 'Contact' },
];

const heroHighlights = ['Admissions', 'Attendance', 'Billing', 'Reporting'];

const platformStats = [
    {
        value: '4 daily workflows',
        label: 'Attendance, grading, billing, and parent notices aligned in one platform.',
    },
    {
        value: '3 core roles',
        label: 'Built for leadership teams, operational staff, and teaching teams.',
    },
    {
        value: '1 source of truth',
        label: 'Academic records, communication, and school operations stay connected.',
    },
];

const programs = [
    {
        title: 'Student records and enrollment',
        description:
            'Keep admissions, class placement, academic history, and attendance in one operating system for your office team.',
        metric: 'From application to class list',
        icon: BookOpen,
        details: [
            'Enrollment intake and status tracking',
            'Class assignment and student history',
            'Daily attendance records',
            'Registrar-friendly student profiles',
        ],
        preview: [
            'New applicants',
            'Returned students',
            'Class placement',
            'Attendance alerts',
        ],
        layout: 'lg:col-span-2 lg:row-span-2',
    },
    {
        title: 'Teaching and learning flow',
        description:
            'Support faculty with lesson delivery, assignment workflows, and term-based evaluation without extra admin overhead.',
        metric: 'Teachers move faster',
        icon: GraduationCap,
        details: [
            'Classroom schedules and rosters',
            'Assignment and assessment tracking',
            'Gradebook visibility by term',
            'Progress snapshots for staff reviews',
        ],
        preview: ['Class schedule', 'Assignment queue', 'Grade review'],
        layout: 'lg:col-span-1',
    },
    {
        title: 'Parent communication and updates',
        description:
            'Send the right message at the right time, whether it is an announcement, attendance follow-up, or academic notice.',
        metric: 'Fewer missed updates',
        icon: MessageSquare,
        details: [
            'Parent-facing updates and reminders',
            'Teacher notices and school announcements',
            'Attendance and behavior follow-ups',
            'A clearer communication trail',
        ],
        preview: [
            'Absence notice sent',
            'Event reminder',
            'Fee due this Friday',
        ],
        layout: 'lg:col-span-1',
    },
    {
        title: 'Billing, reporting, and oversight',
        description:
            'Give leadership a clearer picture of tuition activity, operational follow-up, and school-wide reporting without manual collation.',
        metric: 'Built for school operations',
        icon: BarChart3,
        details: [
            'Billing status visibility',
            'Operational and academic summaries',
            'Role-based dashboards',
            'Reporting for school leadership',
        ],
        preview: ['Tuition status', 'Attendance trend', 'Term summary'],
        layout: 'lg:col-span-2',
    },
];

const whyChoose = [
    {
        title: 'Less admin overhead',
        description:
            'Office teams stop repeating work across multiple tools because student, finance, and communication activity lives in one place.',
        icon: Zap,
    },
    {
        title: 'Stronger parent visibility',
        description:
            'Parents get timely updates, clearer expectations, and an easier way to stay aligned with the school.',
        icon: MessageSquare,
    },
    {
        title: 'Operational confidence',
        description:
            'Leadership gets cleaner reporting, better accountability, and fewer blind spots across day-to-day operations.',
        icon: Briefcase,
    },
    {
        title: 'Secure student data handling',
        description:
            'Sensitive records stay inside a structured system designed for reliable access, permissions, and long-term maintainability.',
        icon: ShieldCheck,
    },
    {
        title: 'Mobile-ready access',
        description:
            'Teachers, staff, and families can use the platform across desktop, tablet, and mobile screens without losing clarity.',
        icon: Smartphone,
    },
    {
        title: 'Room to grow with your school',
        description:
            'Whether you are managing one campus or scaling operations, the system is built to handle broader workflows over time.',
        icon: Database,
    },
];

const testimonials = [
    {
        quote: 'We needed one place to manage attendance, parent communication, and term reporting. Koamishin gave our admin team a cleaner daily rhythm.',
        name: 'Mariam Santos',
        role: 'School administrator',
    },
    {
        quote: 'The biggest improvement was clarity. Teachers knew where updates lived, and parents stopped missing important notices.',
        name: 'Daniel Ofori',
        role: 'Academic coordinator',
    },
    {
        quote: 'What stood out was how practical the workflows felt. It matched how a school actually runs instead of forcing us into generic software patterns.',
        name: 'Leah Okonkwo',
        role: 'Registrar',
    },
];

const supportCategories = [
    'All',
    'Onboarding',
    'Parent access',
    'Finance',
    'Operations',
];

const faqs = [
    {
        category: 'Onboarding',
        question: 'How long does a typical school rollout take?',
        answer: 'Most schools can start with their essential workflows in a few weeks. The exact pace depends on the quality of existing records and how many modules you want live first.',
    },
    {
        category: 'Onboarding',
        question: 'Can we start with only a few core workflows first?',
        answer: 'Yes. Many schools begin with enrollment, attendance, and communication, then expand into reporting and billing once staff are comfortable.',
    },
    {
        category: 'Parent access',
        question: 'Can parents and guardians have separate access?',
        answer: 'Yes. Parent-facing access can be structured around the student record so families can follow attendance, updates, and key notices more easily.',
    },
    {
        category: 'Parent access',
        question: 'Will teachers still control how communication is sent?',
        answer: 'Yes. Schools can keep communication organized by role so classroom updates, school-wide notices, and administrative reminders are handled clearly.',
    },
    {
        category: 'Finance',
        question: 'Can the platform support tuition and payment follow-up?',
        answer: 'Yes. Koamishin is designed to give your team better billing visibility, clearer status tracking, and a simpler way to follow up on outstanding items.',
    },
    {
        category: 'Operations',
        question: 'Is Koamishin only for large institutions?',
        answer: 'No. It works well for growing schools that need structure now, as well as larger institutions that want more coordinated operations across teams.',
    },
    {
        category: 'Operations',
        question: 'Can we connect this with our existing school processes?',
        answer: 'Yes. The goal is to fit the platform around how your school already operates, then improve the parts that are slowing your team down.',
    },
];

const supportCards = [
    {
        title: 'Setup planning',
        description:
            'Get a guided view of which workflows to set up first and what your team needs to prepare.',
        icon: Calendar,
        points: [
            'School structure review',
            'Priority module planning',
            'Admin onboarding support',
        ],
    },
    {
        title: 'Parent access guidance',
        description:
            'Clarify how communication, visibility, and guardian access should work before setup begins.',
        icon: Users,
        points: [
            'Guardian communication flow',
            'Notification expectations',
            'Role-specific access planning',
        ],
    },
    {
        title: 'Operational readiness',
        description:
            'Review your data, reporting expectations, and day-to-day admin processes before launch.',
        icon: Globe,
        points: [
            'Data preparation',
            'Reporting expectations',
            'Daily workflow mapping',
        ],
    },
];

const techStack = [
    'Laravel 13',
    'Vue 3',
    'TypeScript',
    'Tailwind CSS',
    'Inertia.js',
    'MySQL',
    'PHP 8.5',
    'Vite',
    'Octane',
    'Wayfinder',
    'Pest',
    'PostgreSQL',
];

const activeSection = ref('hero');
const mobileMenuOpen = ref(false);
const activeCategory = ref('All');
const supportSearch = ref('');
const openFaq = ref<string | null>(faqs[0]?.question ?? null);
const trackedSectionIds = [
    'hero',
    'programs',
    'impact',
    'why',
    'faq',
    'contact',
] as const;

const filteredFaqs = computed(() => {
    const query = supportSearch.value.trim().toLowerCase();

    return faqs.filter((faq) => {
        const matchesCategory =
            activeCategory.value === 'All' ||
            faq.category === activeCategory.value;
        const haystack =
            `${faq.question} ${faq.answer} ${faq.category}`.toLowerCase();

        return (
            matchesCategory && (query.length === 0 || haystack.includes(query))
        );
    });
});

const currentYear = new Date().getFullYear();

let lenisInstance: Lenis | null = null;
let rafId: number | null = null;
let revealObserver: IntersectionObserver | null = null;
let trackedSections: HTMLElement[] = [];

const updateActiveSection = () => {
    if (trackedSections.length === 0) {
        return;
    }

    const scrollMarker = window.scrollY + 180;
    const currentSection = trackedSections.reduce(
        (current, section) =>
            section.offsetTop <= scrollMarker ? section.id : current,
        'hero',
    );

    activeSection.value = currentSection;
};

const handleNavClick = (sectionId: string) => {
    activeSection.value = sectionId;
    mobileMenuOpen.value = false;
};

onMounted(() => {
    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    if (!reduceMotion) {
        lenisInstance = new Lenis({
            duration: 1.05,
            easing: (t: number) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            smoothWheel: true,
        });

        const raf = (time: number) => {
            lenisInstance?.raf(time);
            rafId = requestAnimationFrame(raf);
        };

        rafId = requestAnimationFrame(raf);
    }

    const revealElements = Array.from(
        document.querySelectorAll<HTMLElement>('.reveal-element'),
    );

    if (reduceMotion) {
        revealElements.forEach((element) => element.classList.add('revealed'));
    } else {
        revealObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        revealObserver?.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.15 },
        );

        revealElements.forEach((element) => revealObserver?.observe(element));
    }

    trackedSections = trackedSectionIds
        .map((sectionId) => document.getElementById(sectionId))
        .filter(
            (section): section is HTMLElement => section instanceof HTMLElement,
        );

    updateActiveSection();

    window.addEventListener('scroll', updateActiveSection, { passive: true });
    window.addEventListener('resize', updateActiveSection);
});

onUnmounted(() => {
    lenisInstance?.destroy();
    revealObserver?.disconnect();
    window.removeEventListener('scroll', updateActiveSection);
    window.removeEventListener('resize', updateActiveSection);

    if (rafId !== null) {
        cancelAnimationFrame(rafId);
    }
});

const toggleFaq = (question: string) => {
    openFaq.value = openFaq.value === question ? null : question;
};

const setCategory = (category: string) => {
    activeCategory.value = category;
    openFaq.value = null;
};
</script>

<template>
    <Head
        title="Koamishin Academy - School operations, communication, and reporting"
    >
        <meta
            name="description"
            content="Koamishin helps schools run admissions, attendance, grading, parent communication, billing, and reporting from one connected platform."
        />
    </Head>

    <div
        class="relative flex min-h-screen flex-col overflow-x-clip bg-background text-foreground selection:bg-primary selection:text-primary-foreground"
    >
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[60] focus:rounded-full focus:bg-primary focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-primary-foreground"
        >
            Skip to content
        </a>

        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="absolute top-0 left-1/2 h-[34rem] w-[34rem] -translate-x-1/2 rounded-full bg-primary/12 blur-3xl"
            />
            <div
                class="absolute top-[22rem] right-[-8rem] h-[24rem] w-[24rem] rounded-full bg-primary/8 blur-3xl"
            />
            <div
                class="absolute top-[48rem] left-[-8rem] h-[20rem] w-[20rem] rounded-full bg-accent/20 blur-3xl"
            />
        </div>

        <ImpersonateBanner />

        <header
            class="sticky top-0 z-50 border-b border-border/60 bg-background/85 backdrop-blur-xl"
        >
            <div
                class="container mx-auto flex h-18 max-w-7xl items-center justify-between px-6 py-4"
            >
                <a href="#hero" class="flex items-center gap-3">
                    <KoamishinLogo
                        class="h-10 w-10 rounded-2xl shadow-lg shadow-primary/10"
                    />
                    <div class="space-y-0.5">
                        <p class="text-base font-semibold tracking-tight">
                            Koamishin
                        </p>
                        <p class="text-xs text-muted-foreground">
                            School operations platform
                        </p>
                    </div>
                </a>

                <nav
                    class="hidden items-center gap-2 rounded-full border border-border/60 bg-background/80 p-1 md:flex"
                >
                    <a
                        v-for="item in navItems"
                        :key="item.id"
                        :href="`#${item.id}`"
                        class="rounded-full px-4 py-2 text-sm font-medium transition-all duration-200"
                        :class="
                            activeSection === item.id
                                ? 'bg-primary text-primary-foreground shadow-sm shadow-primary/20'
                                : 'text-muted-foreground hover:bg-accent/70 hover:text-foreground'
                        "
                        :aria-current="
                            activeSection === item.id ? 'page' : undefined
                        "
                        @click="handleNavClick(item.id)"
                    >
                        {{ item.label }}
                    </a>
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    <Button as-child variant="ghost" size="sm">
                        <a
                            href="mailto:contact@koamishin.com?subject=Koamishin%20walkthrough"
                            >Book a walkthrough</a
                        >
                    </Button>

                    <template v-if="$page.props.auth.user">
                        <Button as-child size="sm" class="rounded-full px-5">
                            <Link :href="route('dashboard')"
                                >Open dashboard</Link
                            >
                        </Button>
                    </template>
                    <template v-else>
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="route('login')">Log in</Link>
                        </Button>
                        <Button
                            v-if="props.canRegister"
                            as-child
                            size="sm"
                            class="rounded-full px-5"
                        >
                            <Link :href="route('register')"
                                >Create account</Link
                            >
                        </Button>
                    </template>
                </div>

                <Button
                    variant="outline"
                    size="sm"
                    class="md:hidden"
                    :aria-expanded="mobileMenuOpen"
                    aria-controls="mobile-navigation"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                >
                    {{ mobileMenuOpen ? 'Close' : 'Menu' }}
                </Button>
            </div>

            <div
                v-if="mobileMenuOpen"
                id="mobile-navigation"
                class="border-t border-border/60 bg-background/95 px-6 py-4 md:hidden"
            >
                <nav class="flex flex-col gap-2">
                    <a
                        v-for="item in navItems"
                        :key="item.id"
                        :href="`#${item.id}`"
                        class="rounded-xl px-4 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeSection === item.id
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                        "
                        :aria-current="
                            activeSection === item.id ? 'page' : undefined
                        "
                        @click="handleNavClick(item.id)"
                    >
                        {{ item.label }}
                    </a>
                    <a
                        href="mailto:contact@koamishin.com?subject=Koamishin%20walkthrough"
                        class="rounded-xl px-4 py-3 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        @click="mobileMenuOpen = false"
                    >
                        Book a walkthrough
                    </a>
                </nav>
            </div>
        </header>

        <main id="main-content" class="relative flex-1">
            <section
                id="hero"
                class="scroll-mt-28 px-6 pt-8 pb-10 sm:pt-10 lg:pt-12 lg:pb-16"
            >
                <div class="container mx-auto max-w-6xl">
                    <div class="reveal-element relative">
                        <div
                            class="pointer-events-none absolute inset-x-0 top-[-2rem] mx-auto h-[26rem] max-w-4xl rounded-full bg-primary/12 blur-3xl"
                        />
                        <div
                            class="relative mx-auto flex max-w-5xl flex-col items-center text-center"
                        >
                            <Badge
                                variant="secondary"
                                class="rounded-full border border-border/60 bg-background/85 px-4 py-1.5 text-sm backdrop-blur"
                            >
                                Built for private schools, academies, and
                                learning centers
                            </Badge>

                            <h1
                                class="mt-5 max-w-5xl text-4xl font-semibold tracking-[-0.065em] text-balance sm:text-6xl lg:text-7xl"
                            >
                                Run your school from one connected software
                                platform built for operations, records, and
                                reporting.
                            </h1>

                            <p
                                class="mt-5 max-w-3xl text-lg leading-8 text-muted-foreground sm:text-xl"
                            >
                                Koamishin brings admissions, attendance,
                                academic records, billing, and reporting into
                                one connected platform designed for everyday
                                school operations.
                            </p>

                            <div
                                class="mt-6 flex flex-wrap items-center justify-center gap-3"
                            >
                                <span
                                    v-for="highlight in heroHighlights"
                                    :key="highlight"
                                    class="rounded-full border border-border/60 bg-background/70 px-4 py-2 text-sm font-medium text-muted-foreground backdrop-blur"
                                >
                                    {{ highlight }}
                                </span>
                            </div>

                            <div
                                class="mt-8 flex flex-wrap items-center justify-center gap-4"
                            >
                                <Button
                                    v-if="
                                        !$page.props.auth.user &&
                                        props.canRegister
                                    "
                                    as-child
                                    size="lg"
                                    class="h-12 rounded-full px-8"
                                >
                                    <Link :href="route('register')">
                                        Create your school account
                                        <ArrowRight class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button
                                    v-else-if="$page.props.auth.user"
                                    as-child
                                    size="lg"
                                    class="h-12 rounded-full px-8"
                                >
                                    <Link :href="route('dashboard')">
                                        Open dashboard
                                        <ArrowRight class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button
                                    as-child
                                    variant="outline"
                                    size="lg"
                                    class="h-12 rounded-full px-8"
                                >
                                    <a href="#programs">
                                        Explore capabilities
                                        <ChevronRight class="h-4 w-4" />
                                    </a>
                                </Button>
                            </div>

                            <div
                                class="mt-10 h-px w-full max-w-4xl bg-gradient-to-r from-transparent via-primary/35 to-transparent"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section id="programs" class="scroll-mt-28 px-6 py-14 lg:py-18">
                <div class="container mx-auto max-w-7xl">
                    <div
                        class="mb-10 grid gap-6 border-b border-border/60 pb-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)] lg:items-end"
                    >
                        <div class="reveal-element max-w-3xl">
                            <Badge
                                variant="secondary"
                                class="mb-4 rounded-full px-4 py-1.5 text-sm"
                            >
                                Platform capabilities
                            </Badge>
                            <h2
                                class="text-3xl font-semibold tracking-[-0.04em] text-balance sm:text-5xl"
                            >
                                A school operating stack, presented with
                                pricing-style clarity.
                            </h2>
                        </div>
                        <p
                            class="reveal-element max-w-2xl text-base leading-7 text-muted-foreground lg:justify-self-end"
                        >
                            Each capability behaves like a focused layer in one
                            connected system, so staff can understand what each
                            area owns without feeling overwhelmed.
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Card
                            v-for="(feature, index) in programs"
                            :key="feature.title"
                            class="group reveal-element flex h-full flex-col rounded-[1.75rem] border-border/60 bg-card/85 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-primary/35 hover:shadow-xl hover:shadow-primary/8"
                            :style="{ animationDelay: `${index * 80}ms` }"
                        >
                            <CardHeader class="space-y-5 pb-4">
                                <div
                                    class="flex items-center justify-between gap-4"
                                >
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 transition-colors group-hover:bg-primary/15"
                                    >
                                        <component
                                            :is="feature.icon"
                                            class="h-6 w-6 text-primary"
                                        />
                                    </div>
                                    <span
                                        class="rounded-full border border-border/60 bg-background/80 px-3 py-1 text-[11px] font-medium tracking-[0.1em] text-muted-foreground uppercase"
                                    >
                                        {{ feature.metric }}
                                    </span>
                                </div>

                                <div
                                    class="space-y-3 border-b border-border/60 pb-5"
                                >
                                    <CardTitle class="text-xl tracking-tight">
                                        {{ feature.title }}
                                    </CardTitle>
                                    <CardDescription class="text-sm leading-6">
                                        {{ feature.description }}
                                    </CardDescription>
                                </div>
                            </CardHeader>

                            <CardContent class="flex flex-1 flex-col gap-5">
                                <div class="space-y-3">
                                    <div
                                        v-for="detail in feature.details"
                                        :key="detail"
                                        class="flex items-start gap-3 text-sm text-muted-foreground"
                                    >
                                        <CheckCircle2
                                            class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"
                                        />
                                        <span>{{ detail }}</span>
                                    </div>
                                </div>

                                <div class="mt-auto space-y-3 pt-2">
                                    <p
                                        class="text-[11px] font-medium tracking-[0.12em] text-muted-foreground uppercase"
                                    >
                                        Typical school view
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="item in feature.preview"
                                            :key="item"
                                            class="rounded-full bg-accent/80 px-3 py-1.5 text-xs font-medium text-accent-foreground"
                                        >
                                            {{ item }}
                                        </span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <section id="impact" class="scroll-mt-28 px-6 py-14 lg:py-18">
                <div class="container mx-auto max-w-7xl">
                    <div
                        class="grid gap-10 border-t border-border/60 pt-10 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]"
                    >
                        <div class="reveal-element max-w-2xl space-y-5">
                            <Badge
                                variant="secondary"
                                class="rounded-full px-4 py-1.5 text-sm"
                            >
                                Impact in practice
                            </Badge>
                            <h2
                                class="text-3xl font-semibold tracking-[-0.04em] text-balance sm:text-5xl"
                            >
                                Clearer operations, shown as momentum instead of
                                dashboard clutter.
                            </h2>
                            <p
                                class="text-base leading-7 text-muted-foreground sm:text-lg sm:leading-8"
                            >
                                The value is not another wall of widgets. It is
                                a steadier rhythm for leadership, admin teams,
                                teachers, and families working from the same
                                flow.
                            </p>
                        </div>

                        <div
                            class="grid gap-6 sm:grid-cols-3 sm:gap-4 lg:gap-6"
                        >
                            <div
                                v-for="stat in platformStats"
                                :key="stat.value"
                                class="reveal-element border-b border-border/60 pb-5 sm:border-b-0 sm:border-l sm:pl-4 first:sm:border-l-0 first:sm:pl-0"
                            >
                                <p
                                    class="text-2xl font-semibold tracking-tight sm:text-3xl"
                                >
                                    {{ stat.value }}
                                </p>
                                <p
                                    class="mt-3 text-sm leading-6 text-muted-foreground"
                                >
                                    {{ stat.label }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-10 divide-y divide-border/60 border-y border-border/60"
                    >
                        <div
                            v-for="(quote, index) in testimonials"
                            :key="quote.name"
                            class="reveal-element grid gap-4 py-6 lg:grid-cols-[2rem_minmax(0,1fr)_15rem] lg:items-start lg:gap-6"
                            :style="{ animationDelay: `${index * 70}ms` }"
                        >
                            <p class="text-2xl leading-none text-primary/65">
                                “
                            </p>
                            <p
                                class="text-base leading-7 text-foreground/90 sm:text-lg"
                            >
                                {{ quote.quote }}
                            </p>
                            <div
                                class="space-y-1 text-sm text-muted-foreground lg:text-right"
                            >
                                <p
                                    class="font-semibold tracking-tight text-foreground"
                                >
                                    {{ quote.name }}
                                </p>
                                <p>{{ quote.role }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="why" class="scroll-mt-28 px-6 py-16 lg:py-24">
                <div
                    class="container mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.95fr_minmax(0,1.05fr)]"
                >
                    <div class="reveal-element lg:sticky lg:top-28 lg:h-fit">
                        <Badge
                            variant="secondary"
                            class="mb-5 rounded-full px-4 py-1.5 text-sm"
                        >
                            Why schools choose it
                        </Badge>
                        <h2
                            class="text-3xl font-semibold tracking-[-0.04em] text-balance sm:text-5xl"
                        >
                            Better operational flow, not just another dashboard.
                        </h2>
                        <p
                            class="mt-5 max-w-xl text-lg leading-8 text-muted-foreground"
                        >
                            The goal is simple: help your school act faster,
                            communicate more clearly, and keep student
                            information dependable across the people who use it
                            every day.
                        </p>

                        <div
                            class="mt-8 rounded-[1.75rem] border border-border/60 bg-card/85 p-5 shadow-sm"
                        >
                            <p class="text-sm font-semibold tracking-tight">
                                What leadership gets
                            </p>
                            <ul
                                class="mt-4 space-y-3 text-sm leading-6 text-muted-foreground"
                            >
                                <li class="flex items-start gap-3">
                                    <CheckCircle2
                                        class="mt-1 h-4 w-4 shrink-0 text-emerald-500"
                                    />
                                    Cleaner visibility across admin and academic
                                    processes.
                                </li>
                                <li class="flex items-start gap-3">
                                    <CheckCircle2
                                        class="mt-1 h-4 w-4 shrink-0 text-emerald-500"
                                    />
                                    Less time chasing updates across
                                    disconnected tools.
                                </li>
                                <li class="flex items-start gap-3">
                                    <CheckCircle2
                                        class="mt-1 h-4 w-4 shrink-0 text-emerald-500"
                                    />
                                    A structure that can expand with more
                                    workflows over time.
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <Card
                            v-for="(item, index) in whyChoose"
                            :key="item.title"
                            class="reveal-element rounded-[1.75rem] border-border/60 bg-card/85 shadow-sm"
                            :style="{ animationDelay: `${index * 70}ms` }"
                        >
                            <CardHeader class="space-y-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10"
                                >
                                    <component
                                        :is="item.icon"
                                        class="h-6 w-6 text-primary"
                                    />
                                </div>
                                <CardTitle class="text-xl tracking-tight">{{
                                    item.title
                                }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <CardDescription class="text-sm leading-7">{{
                                    item.description
                                }}</CardDescription>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <section id="faq" class="scroll-mt-28 px-6 py-16 lg:py-24">
                <div
                    class="container mx-auto grid max-w-7xl gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]"
                >
                    <div>
                        <div class="reveal-element mb-10">
                            <Badge
                                variant="secondary"
                                class="mb-5 rounded-full px-4 py-1.5 text-sm"
                            >
                                Support and common questions
                            </Badge>
                            <h2
                                class="text-3xl font-semibold tracking-[-0.04em] text-balance sm:text-5xl"
                            >
                                Browse common questions, then move into a more
                                confident school setup conversation.
                            </h2>
                            <p
                                class="mt-5 max-w-3xl text-lg leading-8 text-muted-foreground"
                            >
                                Filter by concern, scan answers quickly, and
                                understand what kind of setup guidance is
                                available before your team gets started.
                            </p>
                        </div>

                        <div
                            class="reveal-element mb-6 rounded-[1.75rem] border border-border/60 bg-card/85 p-5 shadow-sm"
                        >
                            <div
                                class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-center"
                            >
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="category in supportCategories"
                                        :key="category"
                                        type="button"
                                        class="rounded-full px-4 py-2 text-sm font-medium transition-colors"
                                        :class="
                                            activeCategory === category
                                                ? 'bg-primary text-primary-foreground'
                                                : 'border border-border/60 bg-background text-muted-foreground hover:text-foreground'
                                        "
                                        @click="setCategory(category)"
                                    >
                                        {{ category }}
                                    </button>
                                </div>
                                <div>
                                    <Input
                                        v-model="supportSearch"
                                        class="h-11 rounded-full bg-background"
                                        type="search"
                                        placeholder="Search setup, billing, parent access..."
                                        aria-label="Search support questions"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="filteredFaqs.length"
                            class="overflow-hidden rounded-[1.5rem] border border-border/60 bg-card/85 shadow-sm"
                        >
                            <div
                                v-for="(faq, index) in filteredFaqs"
                                :key="faq.question"
                                class="border-b border-border/60 last:border-b-0"
                            >
                                <button
                                    type="button"
                                    class="flex w-full items-start justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-accent/30 sm:px-6"
                                    :aria-expanded="openFaq === faq.question"
                                    :aria-controls="`faq-panel-${index}`"
                                    @click="toggleFaq(faq.question)"
                                >
                                    <div class="space-y-1.5">
                                        <span
                                            class="inline-flex rounded-full bg-accent px-3 py-1 text-xs font-medium text-accent-foreground"
                                        >
                                            {{ faq.category }}
                                        </span>
                                        <p
                                            class="text-base font-semibold tracking-tight sm:text-lg"
                                        >
                                            {{ faq.question }}
                                        </p>
                                    </div>
                                    <ChevronDown
                                        class="mt-1 h-5 w-5 shrink-0 text-muted-foreground transition-transform duration-200"
                                        :class="
                                            openFaq === faq.question
                                                ? 'rotate-180'
                                                : ''
                                        "
                                    />
                                </button>
                                <div
                                    :id="`faq-panel-${index}`"
                                    v-show="openFaq === faq.question"
                                    class="px-5 pb-4 text-sm leading-6 text-muted-foreground sm:px-6"
                                >
                                    <div class="max-w-3xl pr-8">
                                        {{ faq.answer }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <Card
                            v-else
                            class="rounded-[1.5rem] border-border/60 bg-card/85 shadow-sm"
                        >
                            <CardContent class="p-6">
                                <p class="font-semibold tracking-tight">
                                    No support topics matched your search.
                                </p>
                                <p
                                    class="mt-2 text-sm leading-7 text-muted-foreground"
                                >
                                    Try a broader term like attendance, billing,
                                    onboarding, or parent access.
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <aside class="space-y-4 lg:pt-24">
                        <Card
                            v-for="card in supportCards"
                            :key="card.title"
                            class="reveal-element rounded-[1.5rem] border-border/60 bg-card/90 shadow-sm"
                        >
                            <CardHeader class="space-y-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10"
                                >
                                    <component
                                        :is="card.icon"
                                        class="h-6 w-6 text-primary"
                                    />
                                </div>
                                <div>
                                    <CardTitle class="text-xl tracking-tight">{{
                                        card.title
                                    }}</CardTitle>
                                    <CardDescription
                                        class="mt-2 text-sm leading-7"
                                        >{{ card.description }}</CardDescription
                                    >
                                </div>
                            </CardHeader>
                            <CardContent>
                                <ul
                                    class="space-y-3 text-sm text-muted-foreground"
                                >
                                    <li
                                        v-for="point in card.points"
                                        :key="point"
                                        class="flex items-start gap-3"
                                    >
                                        <CheckCircle2
                                            class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"
                                        />
                                        <span>{{ point }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </section>

            <section id="contact" class="scroll-mt-28 px-6 py-16 lg:py-24">
                <div class="reveal-element container mx-auto max-w-6xl">
                    <div
                        class="overflow-hidden rounded-[2.25rem] border border-border/60 bg-gradient-to-br from-card via-card to-primary/10 p-8 shadow-2xl shadow-primary/10 sm:p-10 lg:p-12"
                    >
                        <div
                            class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end"
                        >
                            <div>
                                <Badge
                                    variant="secondary"
                                    class="mb-5 rounded-full px-4 py-1.5 text-sm"
                                >
                                    Ready for a closer look?
                                </Badge>
                                <h2
                                    class="text-3xl font-semibold tracking-[-0.04em] text-balance sm:text-5xl"
                                >
                                    Bring your school’s daily operations into
                                    one clearer system.
                                </h2>
                                <p
                                    class="mt-5 max-w-2xl text-lg leading-8 text-muted-foreground"
                                >
                                    Whether you are cleaning up enrollment,
                                    improving parent updates, or trying to
                                    reduce admin duplication, Koamishin gives
                                    your team a more connected way to work.
                                </p>
                                <div
                                    class="mt-6 flex flex-wrap gap-3 text-sm text-muted-foreground"
                                >
                                    <span
                                        class="rounded-full border border-border/60 bg-background/80 px-4 py-2"
                                        >Guided onboarding available</span
                                    >
                                    <span
                                        class="rounded-full border border-border/60 bg-background/80 px-4 py-2"
                                        >Works for small and growing
                                        schools</span
                                    >
                                    <span
                                        class="rounded-full border border-border/60 bg-background/80 px-4 py-2"
                                        >Built for real school workflows</span
                                    >
                                </div>
                            </div>

                            <div
                                class="flex flex-col gap-3 sm:flex-row lg:flex-col"
                            >
                                <Button
                                    v-if="
                                        !$page.props.auth.user &&
                                        props.canRegister
                                    "
                                    as-child
                                    size="lg"
                                    class="h-12 rounded-full px-8"
                                >
                                    <Link :href="route('register')"
                                        >Create your school account</Link
                                    >
                                </Button>
                                <Button
                                    v-else-if="$page.props.auth.user"
                                    as-child
                                    size="lg"
                                    class="h-12 rounded-full px-8"
                                >
                                    <Link :href="route('dashboard')"
                                        >Open dashboard</Link
                                    >
                                </Button>
                                <Button
                                    as-child
                                    variant="outline"
                                    size="lg"
                                    class="h-12 rounded-full px-8"
                                >
                                    <a
                                        href="mailto:contact@koamishin.com?subject=Koamishin%20walkthrough"
                                        >Book a live walkthrough</a
                                    >
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="relative overflow-hidden border-y border-border/60 bg-accent/25 py-8"
            >
                <div class="container mx-auto mb-4 max-w-7xl px-6">
                    <p
                        class="text-center text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase"
                    >
                        Built on a dependable modern foundation
                    </p>
                </div>
                <div class="relative">
                    <div class="animate-scroll flex gap-16 px-8">
                        <span
                            v-for="(tech, index) in [
                                ...techStack,
                                ...techStack,
                            ]"
                            :key="`${tech}-${index}`"
                            class="shrink-0 text-xl font-semibold tracking-[0.28em] text-muted-foreground/35 uppercase sm:text-2xl"
                        >
                            {{ tech }}
                        </span>
                    </div>
                </div>
            </section>
        </main>

        <footer
            class="border-t border-border/60 bg-card/80 px-6 py-12 backdrop-blur"
        >
            <div class="container mx-auto max-w-7xl">
                <div
                    class="grid gap-10 lg:grid-cols-[minmax(0,1.1fr)_0.7fr_0.8fr]"
                >
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <KoamishinLogo class="h-10 w-10 rounded-2xl" />
                            <div>
                                <p class="text-lg font-semibold tracking-tight">
                                    Koamishin
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    School operations platform
                                </p>
                            </div>
                        </div>
                        <p
                            class="max-w-xl text-sm leading-7 text-muted-foreground"
                        >
                            Designed for schools that want clearer operations,
                            stronger communication, and a more dependable way to
                            manage the day-to-day work behind learning.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <h3
                            class="text-sm font-semibold tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Explore
                        </h3>
                        <ul class="space-y-3 text-sm text-muted-foreground">
                            <li>
                                <a
                                    href="#programs"
                                    class="transition-colors hover:text-foreground"
                                    >Capabilities</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#impact"
                                    class="transition-colors hover:text-foreground"
                                    >Impact</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#why"
                                    class="transition-colors hover:text-foreground"
                                    >Why Koamishin</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#faq"
                                    class="transition-colors hover:text-foreground"
                                    >Support</a
                                >
                            </li>
                        </ul>
                    </div>

                    <div class="space-y-4">
                        <h3
                            class="text-sm font-semibold tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Contact
                        </h3>
                        <ul class="space-y-3 text-sm text-muted-foreground">
                            <li>
                                <a
                                    href="mailto:contact@koamishin.com"
                                    class="transition-colors hover:text-foreground"
                                >
                                    contact@koamishin.com
                                </a>
                            </li>
                            <li>
                                <a
                                    href="mailto:contact@koamishin.com?subject=Koamishin%20walkthrough"
                                    class="transition-colors hover:text-foreground"
                                >
                                    Book a walkthrough
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#contact"
                                    class="transition-colors hover:text-foreground"
                                    >Start with a guided setup conversation</a
                                >
                            </li>
                        </ul>
                    </div>
                </div>

                <div
                    class="mt-10 border-t border-border/60 pt-6 text-sm text-muted-foreground"
                >
                    <p>
                        © {{ currentYear }} Koamishin. Built for modern school
                        operations.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
:global(html) {
    scroll-behavior: smooth;
}

.reveal-element {
    opacity: 0;
    transform: translate3d(0, 24px, 0);
    transition:
        opacity 0.65s ease,
        transform 0.65s ease;
}

.revealed {
    opacity: 1;
    transform: translate3d(0, 0, 0);
}

@keyframes marquee {
    from {
        transform: translateX(0);
    }

    to {
        transform: translateX(-50%);
    }
}

.animate-scroll {
    display: inline-flex;
    min-width: max-content;
    animation: marquee 28s linear infinite;
}

@media (prefers-reduced-motion: reduce) {
    :global(html) {
        scroll-behavior: auto;
    }

    .reveal-element,
    .revealed,
    .animate-scroll {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
        transition: none !important;
    }
}
</style>
