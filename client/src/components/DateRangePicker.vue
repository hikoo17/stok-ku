<script setup>
import { useId } from 'vue'
import FormField from './FormField.vue'

const props = defineProps({
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
  fromError: {
    type: String,
    default: '',
  },
  toError: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:from', 'update:to'])
const fromId = useId()
const toId = useId()
</script>

<template>
  <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
    <FormField :label="fromLabel" :for-id="fromId" :error="fromError">
      <input
        :id="fromId"
        :value="from"
        type="date"
        class="field-input"
        :class="{ 'field-input-error': fromError }"
        :max="max"
        @input="emit('update:from', $event.target.value)"
      />
    </FormField>
    <FormField :label="toLabel" :for-id="toId" :error="toError">
      <input
        :id="toId"
        :value="to"
        type="date"
        class="field-input"
        :class="{ 'field-input-error': toError }"
        :min="from"
        :max="max"
        @input="emit('update:to', $event.target.value)"
      />
    </FormField>
  </div>
</template>
