<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean, null],
    default: '',
  },
  options: {
    type: Array,
    required: true,
  },
  label: {
    type: String,
    default: '',
  },
  id: {
    type: String,
    default: undefined,
  },
  ariaLabel: {
    type: String,
    default: 'Filter',
  },
})

const emit = defineEmits(['update:modelValue'])
const generatedId = useId()
const fieldId = computed(() => props.id || generatedId)
</script>

<template>
  <div class="w-full">
    <label v-if="label" :for="fieldId" class="field-label">{{ label }}</label>
    <select
      :id="fieldId"
      :value="modelValue"
      class="field-input cursor-pointer"
      :aria-label="label || ariaLabel"
      @change="emit('update:modelValue', $event.target.value)"
    >
      <option v-for="option in options" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
  </div>
</template>
