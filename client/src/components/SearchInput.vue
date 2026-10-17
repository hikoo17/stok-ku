<script setup>
import { Search, X } from 'lucide-vue-next'

defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Cari...',
  },
  label: {
    type: String,
    default: 'Cari data',
  },
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div class="relative w-full">
    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
      <Search class="h-4 w-4" aria-hidden="true" />
    </span>
    <input
      :value="modelValue"
      type="search"
      class="field-input pl-9 pr-9"
      :placeholder="placeholder"
      :aria-label="label"
      @input="emit('update:modelValue', $event.target.value)"
    />
    <button
      v-if="modelValue"
      type="button"
      class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 transition hover:text-slate-600"
      aria-label="Hapus pencarian"
      @click="emit('update:modelValue', '')"
    >
      <X class="h-4 w-4" aria-hidden="true" />
    </button>
  </div>
</template>
