<script setup>
import { CircleAlert, RefreshCw } from 'lucide-vue-next'

defineProps({
  message: {
    type: String,
    default: 'Data gagal dimuat. Silakan coba lagi.',
  },
  retryLabel: {
    type: String,
    default: 'Coba Lagi',
  },
})

defineEmits(['retry'])
</script>

<template>
  <div class="flex flex-col items-center justify-center px-6 py-12 text-center" role="alert">
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-500">
      <CircleAlert class="h-6 w-6" aria-hidden="true" />
    </div>
    <h3 class="mt-4 text-sm font-semibold text-slate-900">Terjadi Kesalahan</h3>
    <p class="mt-1 max-w-md text-sm text-slate-500">{{ message }}</p>
    <button type="button" class="btn btn-secondary mt-5" @click="$emit('retry')">
      <RefreshCw class="h-3.5 w-3.5" aria-hidden="true" />
      {{ retryLabel }}
    </button>
  </div>
</template>
