<script setup>
import Breadcrumbs from './Breadcrumbs.vue'

defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  breadcrumbs: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <div class="mb-6">
    <Breadcrumbs v-if="breadcrumbs.length" :items="breadcrumbs" class="mb-3" />

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0">
        <h1 class="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">{{ title }}</h1>
        <p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p>
        <slot name="meta" />
      </div>

      <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>
