<script setup>
import Skeleton from './Skeleton.vue'

defineProps({
  columns: {
    type: Array,
    required: true,
  },
  rows: {
    type: Number,
    default: 5,
  },
})

const widths = ['4rem', '10rem', '7rem', '3.5rem', '5rem', '5rem', '4.5rem', '6rem', '5rem']
</script>

<template>
  <tr v-for="row in rows" :key="row" class="border-b border-slate-100">
    <td
      v-for="(column, index) in columns"
      :key="column.key"
      class="table-td"
      :class="column.align === 'right' ? 'text-right' : column.align === 'center' ? 'text-center' : ''"
    >
      <Skeleton
        :width="column.key === 'actions' ? '5rem' : column.skeletonWidth || widths[index % widths.length]"
        :height="column.key === 'actions' ? '2.25rem' : '0.875rem'"
        :class="[
          column.align === 'right' ? 'ml-auto' : '',
          column.align === 'center' ? 'mx-auto' : '',
        ]"
      />
    </td>
  </tr>
</template>
