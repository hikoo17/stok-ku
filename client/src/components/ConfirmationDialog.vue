<script setup>
import { TriangleAlert } from 'lucide-vue-next'
import AppModal from './AppModal.vue'
import LoadingSpinner from './LoadingSpinner.vue'

defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Konfirmasi',
  },
  message: {
    type: String,
    default: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
  },
  confirmLabel: {
    type: String,
    default: 'Ya, Lanjutkan',
  },
  cancelLabel: {
    type: String,
    default: 'Batal',
  },
  variant: {
    type: String,
    default: 'danger',
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'confirm'])
</script>

<template>
  <AppModal
    :model-value="modelValue"
    size="sm"
    :closable="!loading"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div class="flex items-start gap-4">
      <div
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
        :class="variant === 'danger' ? 'bg-red-50 text-red-600' : 'bg-indigo-50 text-indigo-600'"
      >
        <TriangleAlert class="h-5 w-5" aria-hidden="true" />
      </div>
      <div class="min-w-0 pt-0.5">
        <h2 class="text-base font-semibold text-slate-900">{{ title }}</h2>
        <p class="mt-1.5 text-sm leading-6 text-slate-500">{{ message }}</p>
      </div>
    </div>

    <template #footer>
      <button
        type="button"
        class="btn btn-secondary"
        :disabled="loading"
        @click="emit('update:modelValue', false)"
      >
        {{ cancelLabel }}
      </button>
      <button
        type="button"
        class="btn"
        :class="variant === 'danger' ? 'btn-danger' : 'btn-primary'"
        :disabled="loading"
        @click="emit('confirm')"
      >
        <LoadingSpinner v-if="loading" />
        {{ confirmLabel }}
      </button>
    </template>
  </AppModal>
</template>
