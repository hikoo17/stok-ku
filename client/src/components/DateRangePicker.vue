<script setup>
import FormField from './FormField.vue'

defineProps({
  from: {
    type: String,
    default: '',
  },
  to: {
    type: String,
    default: '',
  },
  fromLabel: {
    type: String,
    default: 'Dari Tanggal',
  },
  toLabel: {
    type: String,
    default: 'Sampai Tanggal',
  },
  max: {
    type: String,
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:from', 'update:to'])
</script>

<template>
  <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
    <FormField :label="fromLabel" :error="error">
      <input
        :value="from"
        type="date"
        class="field-input"
        :class="{ 'field-input-error': error }"
        :max="max"
        @input="emit('update:from', $event.target.value)"
      />
    </FormField>
    <FormField :label="toLabel">
      <input
        :value="to"
        type="date"
        class="field-input"
        :min="from"
        :max="max"
        @input="emit('update:to', $event.target.value)"
      />
    </FormField>
  </div>
</template>
