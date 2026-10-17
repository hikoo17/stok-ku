<script setup>
import { RouterLink } from 'vue-router'
import { ChevronRight } from 'lucide-vue-next'

defineProps({
  items: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <nav v-if="items.length" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-1 text-xs text-slate-500">
      <li v-for="(item, index) in items" :key="`${item.label}-${index}`" class="flex items-center gap-1">
        <RouterLink
          v-if="item.to && index < items.length - 1"
          :to="item.to"
          class="transition-colors hover:text-slate-700"
        >
          {{ item.label }}
        </RouterLink>
        <span v-else :class="index === items.length - 1 ? 'font-medium text-slate-700' : ''">
          {{ item.label }}
        </span>
        <ChevronRight
          v-if="index < items.length - 1"
          class="h-3 w-3 text-slate-300"
          aria-hidden="true"
        />
      </li>
    </ol>
  </nav>
</template>
