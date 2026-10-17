<script setup>
import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-vue-next'
import EmptyState from './EmptyState.vue'
import SkeletonTable from './SkeletonTable.vue'

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },
  rows: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  rowKey: {
    type: String,
    default: 'id',
  },
  sortBy: {
    type: String,
    default: '',
  },
  sortDirection: {
    type: String,
    default: 'desc',
  },
  emptyTitle: {
    type: String,
    default: 'Belum ada data',
  },
  emptyMessage: {
    type: String,
    default: 'Data akan tampil di sini setelah ditambahkan.',
  },
  loadingRows: {
    type: Number,
    default: 5,
  },
})

const emit = defineEmits(['sort'])

function resolveValue(row, key) {
  return key.split('.').reduce((value, part) => value?.[part], row)
}

function sort(column) {
  if (!column.sortable) {
    return
  }

  emit('sort', column.key)
}
</script>

<template>
  <div class="overflow-x-auto" :aria-busy="loading">
    <table class="table-base">
      <thead class="table-head">
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            scope="col"
            class="table-th"
            :class="[column.align === 'right' ? 'text-right' : '', column.align === 'center' ? 'text-center' : '']"
          >
            <button
              v-if="column.sortable"
              type="button"
              class="inline-flex items-center gap-1.5 transition-colors hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
              :class="sortBy === column.key ? 'text-slate-900' : ''"
              @click="sort(column)"
            >
              {{ column.label }}
              <ArrowUp v-if="sortBy === column.key && sortDirection === 'asc'" class="h-3 w-3" />
              <ArrowDown v-else-if="sortBy === column.key && sortDirection === 'desc'" class="h-3 w-3" />
              <ChevronsUpDown v-else class="h-3 w-3 text-slate-500" />
            </button>
            <span v-else>{{ column.label }}</span>
          </th>
        </tr>
      </thead>

      <tbody class="divide-y divide-slate-100 bg-white">
        <template v-if="loading">
          <SkeletonTable :columns="columns" :rows="loadingRows" />
        </template>

        <tr v-else-if="rows.length === 0">
          <td :colspan="columns.length" class="px-4 py-2">
            <slot name="empty">
              <EmptyState :title="emptyTitle" :message="emptyMessage" />
            </slot>
          </td>
        </tr>

        <template v-else>
          <tr
            v-for="(row, index) in rows"
            :key="resolveValue(row, rowKey) ?? index"
            class="table-row"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              class="table-td"
              :class="[column.align === 'right' ? 'text-right' : '', column.align === 'center' ? 'text-center' : '', column.class || '']"
            >
              <slot :name="`cell-${column.key}`" :row="row" :value="resolveValue(row, column.key)">
                {{ resolveValue(row, column.key) }}
              </slot>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</template>
