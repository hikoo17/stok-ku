<script setup>
import { Package } from 'lucide-vue-next'

defineProps({
  icon: {
    type: [Object, Function],
    default: () => Package,
  },
  title: {
    type: String,
    default: 'Belum ada data',
  },
  message: {
    type: String,
    default: 'Data akan tampil di sini setelah ditambahkan.',
  },
})
</script>

<template>
  <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
      <component :is="icon" class="h-6 w-6" aria-hidden="true" />
    </div>
    <h3 class="mt-4 text-sm font-semibold text-slate-900">{{ title }}</h3>
    <p class="mt-1 max-w-sm text-sm text-slate-500">{{ message }}</p>
    <div v-if="$slots.action" class="mt-5">
      <slot name="action" />
    </div>
  </div>
</template>
