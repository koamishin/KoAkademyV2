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
import { UsersRound, Clock, CheckCircle2, AlertCircle, Search, Filter } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

type QueueItem = {
  id: number;
  studentName?: string | null;
  studentNumber?: string | null;
  period?: string | null;
  curriculum?: string | null;
  status: string;
  classification?: string | null;
};

const props = defineProps<{
  enrollments: { data: QueueItem[] };
  summary: {
    draft: number;
    pending: number;
    waitlisted: number;
  };
}>();

const columnHelper = createColumnHelper<QueueItem>();
const globalFilter = ref('');
const statusFilter = ref('all');

const columns = [
  columnHelper.accessor('studentName', {
    header: 'Student',
    id: 'student',
  }),
  columnHelper.accessor('period', {
    header: 'Period',
  }),
  columnHelper.accessor('curriculum', {
    header: 'Curriculum',
  }),
  columnHelper.accessor('classification', {
    header: 'Classification',
  }),
  columnHelper.accessor('status', {
    header: 'Status',
    id: 'status',
  }),
];

const table = useVueTable({
  data: computed(() => {
    if (statusFilter.value === 'all') {
      return props.enrollments.data;
    }
    return props.enrollments.data.filter((item) => item.status === statusFilter.value);
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
    label: 'Draft',
    value: 'draft',
    icon: CheckCircle2,
    color: 'var(--color-chart-1)',
  },
  {
    label: 'Pending',
    value: 'pending',
    icon: Clock,
    color: 'var(--color-chart-2)',
  },
  {
    label: 'Waitlisted',
    value: 'waitlisted',
    icon: AlertCircle,
    color: 'var(--color-chart-3)',
  },
];
</script>

<template>
  <Head title="Enrollment Queue" />

  <AppLayout :breadcrumbs="[{ title: 'Enrollment Queue' }]">
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
              <UsersRound class="size-4 text-white" />
            </div>
            <p class="text-sm font-medium text-muted-foreground">Enrollment</p>
          </div>
          <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Enrollment queue</h1>
          <p class="text-sm text-muted-foreground max-w-2xl">
            Follow up students who need enrollment review or approval.
          </p>
        </div>
      </header>

      <!-- Stat Cards -->
      <section class="grid gap-4 sm:grid-cols-3">
        <button
          v-for="(card, index) in statCards"
          :key="card.label"
          @click="statusFilter = statusFilter === card.value ? 'all' : card.value"
          class="group relative overflow-hidden rounded-3xl border border-border bg-card p-6 text-left text-card-foreground shadow-sm transition-all duration-500 hover:shadow-2xl hover:-translate-y-2"
          :class="{
            'border-primary/50 ring-2 ring-primary/30': statusFilter === card.value,
          }"
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
            {{ summary[card.value as keyof typeof summary] }}
          </p>
          <p class="mt-1 text-sm font-semibold text-muted-foreground">{{ card.label }}</p>
        </button>
      </section>

      <!-- Controls -->
      <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative flex-1 max-w-md">
          <Search class="absolute left-4 top-1/2 size-5 -translate-y-1/2 text-muted-foreground" />
          <input
            v-model="globalFilter"
            type="text"
            placeholder="Search students..."
            class="w-full rounded-2xl border border-border bg-card py-3 pl-12 pr-4 text-sm font-medium text-card-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all duration-300"
          />
        </div>
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <Filter class="size-4" />
          <span>Showing {{ table.getRowModel().rows.length }} of {{ enrollments.data.length }} entries</span>
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
                  <!-- Custom student cell -->
                  <template v-if="cell.column.id === 'student'">
                    <div class="flex items-center gap-3">
                      <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                        <UsersRound class="size-5 text-primary" />
                      </div>
                      <div>
                        <p class="font-semibold">
                          {{ row.original.studentName ?? row.original.studentNumber ?? 'Student' }}
                        </p>
                        <p v-if="row.original.studentNumber" class="text-xs text-muted-foreground">
                          #{{ row.original.studentNumber }}
                        </p>
                      </div>
                    </div>
                  </template>
                  <!-- Custom status cell -->
                  <template v-else-if="cell.column.id === 'status'">
                    <span
                      class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold capitalize"
                      :class="{
                        'bg-gray-500/10 text-gray-600 dark:text-gray-400': row.original.status === 'draft',
                        'bg-amber-500/10 text-amber-600 dark:text-amber-400': row.original.status === 'pending',
                        'bg-orange-500/10 text-orange-600 dark:text-orange-400': row.original.status === 'waitlisted',
                        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400': row.original.status === 'approved' || row.original.status === 'active',
                      }"
                    >
                      {{ row.original.status.replace('_', ' ') }}
                    </span>
                  </template>
                  <!-- Default cell -->
                  <template v-else>
                    {{ row.original[cell.column.id as keyof QueueItem] ?? 'N/A' }}
                  </template>
                </td>
              </tr>
              <tr v-if="table.getRowModel().rows.length === 0">
                <td :colspan="columns.length" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center gap-3">
                    <AlertCircle class="size-12 text-muted-foreground" />
                    <p class="text-sm text-muted-foreground">No enrollments are waiting in the queue.</p>
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
