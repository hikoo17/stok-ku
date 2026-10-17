<script setup>
import { Loader2 } from 'lucide-vue-next'

defineProps({
  text: {
    type: String,
    default: 'Memuat data...',
  },
  compact: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <div
    class="flex items-center justify-center gap-2.5 text-slate-500"
    :class="compact ? 'py-6' : 'py-16'"
    role="status"
  >
    <Loader2 class="h-5 w-5 animate-spin text-indigo-600" aria-hidden="true" />
    <span class="text-sm font-medium">{{ text }}</span>
  </div>
</template>
