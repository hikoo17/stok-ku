<script setup>
import { useId } from 'vue'
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
const inputId = useId()
</script>

<template>
  <div class="w-full">
    <label :for="inputId" class="field-label">{{ label }}</label>
    <div class="relative">
      <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
        <Search class="h-4 w-4" aria-hidden="true" />
      </span>
      <input
        :id="inputId"
        :value="modelValue"
        type="search"
        class="field-input pl-9 pr-10"
        :placeholder="placeholder"
        @input="emit('update:modelValue', $event.target.value)"
      />
      <button
        v-if="modelValue"
        type="button"
        class="absolute inset-y-0 right-0 flex w-9 items-center justify-center text-slate-500 transition hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
        aria-label="Hapus pencarian"
        @click="emit('update:modelValue', '')"
      >
        <X class="h-4 w-4" aria-hidden="true" />
      </button>
    </div>
  </div>
</template>
