<script setup>
defineProps({
  small: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <span
    class="inline-block shrink-0 animate-spin rounded-full border-2 border-current border-t-transparent opacity-80"
    :class="small ? 'h-3.5 w-3.5' : 'h-4 w-4'"
    aria-hidden="true"
  />
</template>
