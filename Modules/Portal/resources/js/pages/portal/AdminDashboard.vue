<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';
import {
  CalendarDays,
  ClipboardCheck,
  GraduationCap,
  UsersRound,
  TrendingUp,
  Activity,
  ArrowRight,
  Clock,
  CheckCircle2,
  AlertCircle,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { Bar, Line, Doughnut } from 'vue-chartjs';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as adminApplicationsIndex } from '@/routes/admin/applications';
import { index as adminClassesIndex } from '@/routes/admin/classes';
import { index as adminEnrollmentsIndex } from '@/routes/admin/enrollments';
import type { AppPageProps, BreadcrumbItem } from '@/types';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
);

defineProps<{
  admin: { name: string; campusName: string };
  stats: {
    applicationsInReview: number;
    pendingEnrollments: number;
    activeClasses: number;
    meetingsToday: number;
  };
  applicationQueue: {
    id: number;
    number: string;
    studentName?: string | null;
    period?: string | null;
    program?: string | null;
    status: string;
    submittedAt?: string | null;
  }[];
  enrollmentQueue: {
    id: number;
    studentName?: string | null;
    studentNumber?: string | null;
    period?: string | null;
    curriculum?: string | null;
    status: string;
  }[];
  classes: {
    id: number;
    name: string;
    code: string;
    teacher?: string | null;
    status: string;
    students: number;
  }[];
}>();

const page = usePage<AppPageProps>();
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard' }];

const chartColors = ref({
  chart1: '',
  chart2: '',
  chart3: '',
  chart4: '',
  chart5: '',
  primary: '',
  secondary: '',
  accent: '',
});

function getCSSVariable(name: string): string {
  if (typeof window === 'undefined') return '';
  return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

const enrollmentChartData = computed(() => ({
  labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
  datasets: [
    {
      label: 'Enrollments',
      data: [65, 59, 80, 81, 56, 55, 40],
      borderColor: chartColors.value.chart1,
      backgroundColor: `${chartColors.value.chart1}33`,
      tension: 0.4,
      fill: true,
    },
  ],
}));

const applicationsChartData = computed(() => ({
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
  datasets: [
    {
      label: 'Applications',
      data: [12, 19, 3, 5, 2, 3],
      backgroundColor: [
        `${chartColors.value.chart1}cc`,
        `${chartColors.value.chart2}cc`,
        `${chartColors.value.chart3}cc`,
        `${chartColors.value.chart4}cc`,
        `${chartColors.value.chart5}cc`,
        `${chartColors.value.primary}cc`,
      ],
      borderRadius: 8,
    },
  ],
}));

const classStatusChartData = computed(() => ({
  labels: ['Active', 'Pending', 'Completed'],
  datasets: [
    {
      data: [40, 20, 10],
      backgroundColor: [
        chartColors.value.chart1,
        chartColors.value.chart3,
        chartColors.value.chart4,
      ],
      borderWidth: 0,
    },
  ],
}));

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: getCSSVariable('--color-border') + '50',
      },
    },
    x: {
      grid: {
        display: false,
      },
    },
  },
};

const statCards = [
  {
    label: 'Applications',
    value: 'applicationsInReview',
    icon: ClipboardCheck,
    href: adminApplicationsIndex,
  },
  {
    label: 'Enrollments',
    value: 'pendingEnrollments',
    icon: UsersRound,
    href: adminEnrollmentsIndex,
  },
  {
    label: 'Active Classes',
    value: 'activeClasses',
    icon: GraduationCap,
    href: adminClassesIndex,
  },
  {
    label: 'Meetings Today',
    value: 'meetingsToday',
    icon: CalendarDays,
    href: null,
  },
] as const;

function getStatusColor(status: string) {
  switch (status) {
    case 'approved':
    case 'active':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400';
    case 'pending':
    case 'in_review':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400';
    default:
      return 'bg-gray-500/10 text-gray-600 dark:text-gray-400';
  }
}

function updateColors() {
  chartColors.value = {
    chart1: getCSSVariable('--color-chart-1'),
    chart2: getCSSVariable('--color-chart-2'),
    chart3: getCSSVariable('--color-chart-3'),
    chart4: getCSSVariable('--color-chart-4'),
    chart5: getCSSVariable('--color-chart-5'),
    primary: getCSSVariable('--color-primary'),
    secondary: getCSSVariable('--color-secondary'),
    accent: getCSSVariable('--color-accent'),
  };
}

onMounted(() => {
  updateColors();

  const observer = new MutationObserver(() => {
    updateColors();
  });
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class', 'style'],
  });
});
</script>

<template>
  <Head title="Admin Dashboard" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
      <!-- Header -->
      <header class="flex items-center justify-between rounded-2xl border border-border bg-card p-4 sm:p-6">
        <div>
          <p class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
            <Activity class="size-4" />
            Daily operations
          </p>
          <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">{{ admin.campusName }}</h1>
          <p class="mt-1 text-sm text-muted-foreground">
            A focused admin view for queues, classes, and today’s campus movement.
          </p>
        </div>
        <div class="hidden sm:block">
          <div
            class="flex h-16 w-16 items-center justify-center rounded-xl"
            :style="{
              background: `linear-gradient(135deg, ${chartColors.primary}, ${chartColors.accent})`,
            }"
          >
            <Activity class="size-8 text-white" />
          </div>
        </div>
      </header>

      <!-- Stat Cards -->
      <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <component
          :is="card.href ? Link : 'article'"
          v-for="card in statCards"
          :key="card.label"
          :href="card.href ? card.href({ campus: page.props.currentCampus!.slug }) : undefined"
          class="group relative overflow-hidden rounded-2xl border border-border bg-card p-6 text-card-foreground shadow-sm transition-all duration-300 hover:shadow-lg hover:-translate-y-1"
        >
          <div
            class="absolute left-0 top-0 h-1 w-full"
            :style="{ background: `linear-gradient(90deg, ${chartColors.primary}, ${chartColors.accent})` }"
          ></div>
          <div class="flex items-start justify-between">
            <div
              class="flex h-12 w-12 items-center justify-center rounded-xl"
              :style="{ background: `linear-gradient(135deg, ${chartColors.primary}, ${chartColors.accent})` }"
            >
              <component :is="card.icon" class="size-6 text-white" />
            </div>
            <TrendingUp class="size-5 text-muted-foreground" :style="{ color: chartColors.primary }" />
          </div>
          <p class="mt-5 text-3xl font-bold tracking-tight">{{ stats[card.value] }}</p>
          <p class="mt-1 text-sm font-medium text-muted-foreground">{{ card.label }}</p>
        </component>
      </section>

      <!-- Charts Section -->
      <section class="grid gap-6 lg:grid-cols-3">
        <div class="col-span-2 rounded-2xl border border-border bg-card p-6 text-card-foreground shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-lg font-semibold">Enrollment Trends</h3>
              <p class="text-sm text-muted-foreground">Weekly enrollment activity</p>
            </div>
          </div>
          <div class="h-64">
            <Line :data="enrollmentChartData" :options="chartOptions" />
          </div>
        </div>

        <div class="rounded-2xl border border-border bg-card p-6 text-card-foreground shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-lg font-semibold">Class Status</h3>
              <p class="text-sm text-muted-foreground">Distribution of classes</p>
            </div>
          </div>
          <div class="h-64">
            <Doughnut :data="classStatusChartData" :options="{ ...chartOptions, cutout: '70%' }" />
          </div>
        </div>
      </section>

      <section class="grid gap-6 lg:grid-cols-3">
        <!-- Applications Chart -->
        <div class="rounded-2xl border border-border bg-card p-6 text-card-foreground shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-lg font-semibold">Applications</h3>
              <p class="text-sm text-muted-foreground">Monthly submissions</p>
            </div>
          </div>
          <div class="h-64">
            <Bar :data="applicationsChartData" :options="chartOptions" />
          </div>
        </div>

        <!-- Queues Section -->
        <div class="rounded-2xl border border-border bg-card p-6 text-card-foreground shadow-sm">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Applications queue</h2>
            <Link
              :href="adminApplicationsIndex({ campus: page.props.currentCampus!.slug })"
              class="text-sm font-medium"
              :style="{ color: chartColors.primary }"
            >
              View all
              <ArrowRight class="ml-1 inline size-4" />
            </Link>
          </div>
          <div class="mt-5 space-y-3">
            <article
              v-for="item in applicationQueue"
              :key="item.id"
              class="flex items-start gap-3 rounded-xl bg-muted/50 p-4"
            >
              <div class="mt-1">
                <CheckCircle2 class="size-5" :style="{ color: chartColors.chart2 }" />
              </div>
              <div class="flex-1">
                <p class="font-medium">{{ item.studentName ?? item.number }}</p>
                <div class="mt-1 flex items-center gap-2">
                  <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium" :class="getStatusColor(item.status)">
                    {{ item.status.replace('_', ' ') }}
                  </span>
                  <span class="text-xs text-muted-foreground">
                    <Clock class="mr-1 inline size-3" />
                    {{ item.submittedAt ? new Date(item.submittedAt).toLocaleDateString() : 'N/A' }}
                  </span>
                </div>
              </div>
            </article>
            <div
              v-if="applicationQueue.length === 0"
              class="flex flex-col items-center justify-center py-8 text-center"
            >
              <AlertCircle class="mb-2 size-8 text-muted-foreground" />
              <p class="text-sm text-muted-foreground">No applications need attention.</p>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-border bg-card p-6 text-card-foreground shadow-sm">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Enrollment queue</h2>
            <Link
              :href="adminEnrollmentsIndex({ campus: page.props.currentCampus!.slug })"
              class="text-sm font-medium"
              :style="{ color: chartColors.primary }"
            >
              View all
              <ArrowRight class="ml-1 inline size-4" />
            </Link>
          </div>
          <div class="mt-5 space-y-3">
            <article
              v-for="item in enrollmentQueue"
              :key="item.id"
              class="flex items-start gap-3 rounded-xl bg-muted/50 p-4"
            >
              <div class="mt-1">
                <CheckCircle2 class="size-5" :style="{ color: chartColors.chart2 }" />
              </div>
              <div class="flex-1">
                <p class="font-medium">{{ item.studentName ?? item.studentNumber ?? 'Student' }}</p>
                <div class="mt-1 flex items-center gap-2">
                  <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium" :class="getStatusColor(item.status)">
                    {{ item.status.replace('_', ' ') }}
                  </span>
                </div>
              </div>
            </article>
            <div
              v-if="enrollmentQueue.length === 0"
              class="flex flex-col items-center justify-center py-8 text-center"
            >
              <AlertCircle class="mb-2 size-8 text-muted-foreground" />
              <p class="text-sm text-muted-foreground">No pending enrollments.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Classes Section -->
      <section class="rounded-2xl border border-border bg-card p-6 text-card-foreground shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold">Class operations</h2>
            <p class="text-sm text-muted-foreground">Active classes and their students</p>
          </div>
          <Link
            :href="adminClassesIndex({ campus: page.props.currentCampus!.slug })"
            class="text-sm font-medium"
            :style="{ color: chartColors.primary }"
          >
            View all
            <ArrowRight class="ml-1 inline size-4" />
          </Link>
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="item in classes"
            :key="item.id"
            class="group rounded-xl border border-border bg-muted/50 p-5 transition-all hover:border-primary/50 hover:bg-card"
          >
            <div class="flex items-center justify-between">
              <h3 class="font-semibold">{{ item.name }}</h3>
              <span
                class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                :style="{
                  background: `${chartColors.chart2}20`,
                  color: chartColors.chart2,
                }"
              >
                {{ item.status }}
              </span>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">{{ item.code }}</p>
            <div class="mt-4 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <UsersRound class="size-4 text-muted-foreground" />
                <span class="text-sm font-medium">{{ item.students }} students</span>
              </div>
              <div v-if="item.teacher" class="text-xs text-muted-foreground">
                {{ item.teacher }}
              </div>
            </div>
          </article>
          <div
            v-if="classes.length === 0"
            class="col-span-full flex flex-col items-center justify-center py-12 text-center"
          >
            <AlertCircle class="mb-3 size-10 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">No classes are active yet.</p>
          </div>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
