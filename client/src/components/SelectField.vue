<script setup>
import FormField from './FormField.vue'

defineProps({
  modelValue: {
    type: [String, Number, Boolean, null],
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  id: {
    type: String,
    default: undefined,
  },
  options: {
    type: Array,
    required: true,
  },
  placeholder: {
    type: String,
    default: 'Pilih opsi',
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
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <FormField :label="label" :for-id="id" :required="required" :error="error" :hint="hint">
    <select
      :id="id"
      :value="modelValue"
      class="field-input cursor-pointer"
      :class="{ 'field-input-error': error }"
      :disabled="disabled"
      :required="required"
      :aria-invalid="Boolean(error)"
      @change="emit('update:modelValue', $event.target.value)"
    >
      <option value="" disabled>{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="option.value"
        :value="option.value"
        :disabled="option.disabled"
      >
        {{ option.label }}
      </option>
    </select>
  </FormField>
</template>
