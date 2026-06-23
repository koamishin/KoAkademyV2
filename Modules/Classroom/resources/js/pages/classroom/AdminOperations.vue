<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
  createColumnHelper,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from '@tanstack/vue-table';
import { GraduationCap, UsersRound, AlertCircle, Search, Filter } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

type ClassRow = {
  id: number;
  name: string;
  code: string;
  section?: string | null;
  teacher?: string | null;
  status: string;
  students: number;
};

const props = defineProps<{
  classes: { data: ClassRow[] };
}>();

const columnHelper = createColumnHelper<ClassRow>();
const globalFilter = ref('');
const statusFilter = ref('all');

const columns = [
  columnHelper.accessor('name', {
    header: 'Class',
    id: 'class',
  }),
  columnHelper.accessor('teacher', {
    header: 'Teacher',
  }),
  columnHelper.accessor('students', {
    header: 'Students',
    id: 'students',
  }),
  columnHelper.accessor('status', {
    header: 'Status',
    id: 'status',
  }),
];

const table = useVueTable({
  data: computed(() => {
    if (statusFilter.value === 'all') {
      return props.classes.data;
    }
    return props.classes.data.filter((item) => item.status === statusFilter.value);
  }),
  columns,
  state: {
    globalFilter: globalFilter,
  },
  onGlobalFilterChange: (updater) => {
    globalFilter.value = updater instanceof Function ? updater(globalFilter.value) : updater;
  },
  getCoreRowModel: getCoreRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  initialState: {
    pagination: {
      pageSize: 10,
    },
  },
});

const statCards = [
  {
    label: 'Total Classes',
    value: computed(() => props.classes.data.length),
    icon: GraduationCap,
    color: 'var(--color-chart-1)',
  },
  {
    label: 'Active Classes',
    value: computed(() =>
      props.classes.data.filter((c) => c.status === 'active' || c.status === 'published').length
    ),
    icon: UsersRound,
    color: 'var(--color-chart-2)',
  },
  {
    label: 'Total Students',
    value: computed(() => props.classes.data.reduce((sum, c) => sum + c.students, 0)),
    icon: UsersRound,
    color: 'var(--color-chart-3)',
  },
];
</script>

<template>
  <Head title="Class Operations" />

  <AppLayout :breadcrumbs="[{ title: 'Class Operations' }]">
    <div class="mx-auto flex w-full max-w-[1400px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
      <!-- Header -->
      <header class="flex items-center justify-between rounded-3xl border border-border bg-card p-6 sm:p-8 shadow-sm transition-all duration-500 hover:shadow-xl hover:-translate-y-0.5">
        <div class="space-y-2">
          <div class="flex items-center gap-2">
            <div
              class="flex h-8 w-8 items-center justify-center rounded-lg"
              :style="{
                background: 'linear-gradient(135deg, var(--color-chart-1), var(--color-accent))',
              }"
            >
              <GraduationCap class="size-4 text-white" />
            </div>
            <p class="text-sm font-medium text-muted-foreground">Classroom</p>
          </div>
          <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Class operations</h1>
          <p class="text-sm text-muted-foreground max-w-2xl">
            Monitor classes, assigned faculty, and roster size at a glance.
          </p>
        </div>
      </header>

      <!-- Stat Cards -->
      <section class="grid gap-4 sm:grid-cols-3">
        <article
          v-for="(card, index) in statCards"
          :key="card.label"
          class="group relative overflow-hidden rounded-3xl border border-border bg-card p-6 text-card-foreground shadow-sm transition-all duration-500 hover:shadow-2xl hover:-translate-y-2"
          :style="{
            animationDelay: `${index * 50}ms`,
          }"
        >
          <div
            class="absolute left-0 top-0 h-1 w-full"
            :style="{ background: `linear-gradient(90deg, ${card.color}, var(--color-accent))` }"
          ></div>
          <div class="flex items-start justify-between">
            <div
              class="flex h-14 w-14 items-center justify-center rounded-2xl transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3"
              :style="{ background: `linear-gradient(135deg, ${card.color}, var(--color-accent))` }"
            >
              <component :is="card.icon" class="size-7 text-white" />
            </div>
          </div>
          <p class="mt-5 text-4xl font-bold tracking-tight transition-all duration-300 group-hover:scale-105">
            {{ card.value }}
          </p>
          <p class="mt-1 text-sm font-semibold text-muted-foreground">{{ card.label }}</p>
        </article>
      </section>

      <!-- Controls -->
      <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative flex-1 max-w-md">
          <Search class="absolute left-4 top-1/2 size-5 -translate-y-1/2 text-muted-foreground" />
          <input
            v-model="globalFilter"
            type="text"
            placeholder="Search classes..."
            class="w-full rounded-2xl border border-border bg-card py-3 pl-12 pr-4 text-sm font-medium text-card-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all duration-300"
          />
        </div>
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <Filter class="size-4" />
          <span>Showing {{ table.getRowModel().rows.length }} of {{ classes.data.length }} entries</span>
        </div>
      </section>

      <!-- Table -->
      <section class="overflow-hidden rounded-3xl border border-border bg-card text-card-foreground shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id" class="border-b border-border bg-muted/30">
                <th
                  v-for="header in headerGroup.headers"
                  :key="header.id"
                  class="px-6 py-4 text-left text-sm font-semibold text-muted-foreground"
                  :style="{ width: header.getSize() !== 150 ? `${header.getSize()}px` : 'auto' }"
                >
                  {{ header.column.columnDef.header }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in table.getRowModel().rows"
                :key="row.id"
                class="border-b border-border transition-all duration-300 hover:bg-muted/50"
              >
                <td
                  v-for="cell in row.getVisibleCells()"
                  :key="cell.id"
                  class="px-6 py-5"
                >
                  <!-- Custom class cell -->
                  <template v-if="cell.column.id === 'class'">
                    <div class="flex items-center gap-3">
                      <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                        <GraduationCap class="size-5 text-primary" />
                      </div>
                      <div>
                        <p class="font-semibold">{{ row.original.name }}</p>
                        <p class="text-xs text-muted-foreground">
                          {{ row.original.code }}<span v-if="row.original.section"> · {{ row.original.section }}</span>
                        </p>
                      </div>
                    </div>
                  </template>
                  <!-- Custom students cell -->
                  <template v-else-if="cell.column.id === 'students'">
                    <div class="flex items-center gap-2">
                      <UsersRound class="size-4 text-muted-foreground" />
                      <span class="font-semibold">{{ row.original.students }}</span>
                    </div>
                  </template>
                  <!-- Custom status cell -->
                  <template v-else-if="cell.column.id === 'status'">
                    <span
                      class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold capitalize"
                      :class="{
                        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400': row.original.status === 'active' || row.original.status === 'published',
                        'bg-gray-500/10 text-gray-600 dark:text-gray-400': row.original.status !== 'active' && row.original.status !== 'published',
                      }"
                    >
                      {{ row.original.status }}
                    </span>
                  </template>
                  <!-- Default cell -->
                  <template v-else-if="cell.column.id === 'teacher'">
                    {{ row.original.teacher ?? 'No teacher assigned' }}
                  </template>
                  <template v-else>
                    {{ row.original[cell.column.id as keyof ClassRow] ?? 'N/A' }}
                  </template>
                </td>
              </tr>
              <tr v-if="table.getRowModel().rows.length === 0">
                <td :colspan="columns.length" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center gap-3">
                    <AlertCircle class="size-12 text-muted-foreground" />
                    <p class="text-sm text-muted-foreground">No class offerings are available yet.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between border-t border-border px-6 py-4">
          <div class="flex items-center gap-2 text-sm text-muted-foreground">
            <span>Page</span>
            <span class="font-semibold text-card-foreground">{{ table.getState().pagination.pageIndex + 1 }}</span>
            <span>of</span>
            <span class="font-semibold text-card-foreground">{{ table.getPageCount() }}</span>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="table.setPageIndex(0)"
              :disabled="!table.getCanPreviousPage()"
              class="rounded-xl border border-border bg-card px-4 py-2 text-sm font-medium text-card-foreground transition-all duration-300 hover:bg-muted disabled:opacity-50 disabled:cursor-not-allowed"
            >
              First
            </button>
            <button
              @click="table.previousPage()"
              :disabled="!table.getCanPreviousPage()"
              class="rounded-xl border border-border bg-card px-4 py-2 text-sm font-medium text-card-foreground transition-all duration-300 hover:bg-muted disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              @click="table.nextPage()"
              :disabled="!table.getCanNextPage()"
              class="rounded-xl border border-border bg-card px-4 py-2 text-sm font-medium text-card-foreground transition-all duration-300 hover:bg-muted disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
            <button
              @click="table.setPageIndex(table.getPageCount() - 1)"
              :disabled="!table.getCanNextPage()"
              class="rounded-xl border border-border bg-card px-4 py-2 text-sm font-medium text-card-foreground transition-all duration-300 hover:bg-muted disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Last
            </button>
          </div>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
