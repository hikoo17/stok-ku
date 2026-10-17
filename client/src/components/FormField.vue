<script setup>
import { CircleAlert } from 'lucide-vue-next'

defineProps({
  label: {
    type: String,
    default: '',
  },
  forId: {
    type: String,
    default: undefined,
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <div>
    <label v-if="label" :for="forId" class="field-label">
      {{ label }}
      <span v-if="required" class="text-red-500" aria-hidden="true">*</span>
    </label>

    <slot />

    <p v-if="error" class="field-error" role="alert">
      <CircleAlert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
      <span>{{ error }}</span>
    </p>
    <p v-else-if="hint" class="field-hint">{{ hint }}</p>
  </div>
</template>
