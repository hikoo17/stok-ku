<script setup>
defineProps({
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
</script>

<template>
  <div class="w-full">
    <label v-if="label" :for="id" class="mb-1.5 block text-xs font-medium text-slate-600">
      {{ label }}
    </label>
    <select
      :id="id"
      :value="modelValue"
      class="field-input cursor-pointer py-2.5"
      :aria-label="label || ariaLabel"
      @change="emit('update:modelValue', $event.target.value)"
    >
      <option v-for="option in options" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
  </div>
</template>
