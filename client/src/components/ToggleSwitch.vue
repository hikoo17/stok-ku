<script setup>
defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  label: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  ariaLabel: {
    type: String,
    default: 'Ubah status',
  },
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-4">
    <div class="min-w-0">
      <p class="text-sm font-medium text-slate-800">{{ label }}</p>
      <p v-if="description" class="mt-0.5 text-xs leading-5 text-slate-500">{{ description }}</p>
    </div>
    <button
      type="button"
      role="switch"
      :aria-checked="modelValue"
      :aria-label="ariaLabel"
      class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
      :class="modelValue ? 'bg-emerald-500' : 'bg-slate-300'"
      @click="emit('update:modelValue', !modelValue)"
    >
      <span
        class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
        :class="modelValue ? 'translate-x-6' : 'translate-x-1'"
      />
    </button>
  </div>
</template>
