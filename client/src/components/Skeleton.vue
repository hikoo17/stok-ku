<script setup>
defineProps({
  width: {
    type: String,
    default: '100%',
  },
  height: {
    type: String,
    default: '1rem',
  },
  circle: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <div
    class="skeleton"
    :class="circle ? 'rounded-full' : 'rounded-md'"
    :style="{ width, height }"
    aria-hidden="true"
  />
</template>
