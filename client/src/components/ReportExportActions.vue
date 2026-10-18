<script setup>
import { Download, RefreshCw } from 'lucide-vue-next'
import LoadingSpinner from './LoadingSpinner.vue'

defineProps({
  loading: {
    type: Boolean,
    default: false,
  },
  exporting: {
    type: String,
    default: '',
  },
})

defineEmits(['refresh', 'download'])

const formats = ['csv', 'pdf', 'docx']
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <button
      type="button"
      class="btn-icon-sm"
      :disabled="loading || Boolean(exporting)"
      aria-label="Muat ulang laporan"
      title="Muat ulang laporan"
      @click="$emit('refresh')"
    >
      <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" aria-hidden="true" />
    </button>

    <button
      v-for="format in formats"
      :key="format"
      type="button"
      class="btn btn-secondary btn-sm"
      :disabled="loading || Boolean(exporting)"
      @click="$emit('download', format)"
    >
      <LoadingSpinner v-if="exporting === format" small />
      <Download v-else class="h-4 w-4" aria-hidden="true" />
      {{ exporting === format ? 'Mengunduh...' : `Unduh ${format.toUpperCase()}` }}
    </button>
  </div>
</template>
