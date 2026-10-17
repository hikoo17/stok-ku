<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowUpRight } from 'lucide-vue-next'

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    default: 0,
  },
  icon: {
    type: [Object, Function],
    default: null,
  },
  tone: {
    type: String,
    default: 'indigo',
  },
  hint: {
    type: String,
    default: '',
  },
  to: {
    type: [String, Object],
    default: null,
  },
})

const tones = {
  indigo: { iconBg: 'bg-indigo-50 text-indigo-600', ring: 'hover:border-indigo-200' },
  emerald: { iconBg: 'bg-emerald-50 text-emerald-600', ring: 'hover:border-emerald-200' },
  amber: { iconBg: 'bg-amber-50 text-amber-600', ring: 'hover:border-amber-200' },
  red: { iconBg: 'bg-red-50 text-red-600', ring: 'hover:border-red-200' },
  slate: { iconBg: 'bg-slate-100 text-slate-600', ring: 'hover:border-slate-300' },
}

const styles = computed(() => tones[props.tone] || tones.indigo)
</script>

<template>
  <component
    :is="to ? RouterLink : 'div'"
    :to="to || undefined"
    class="card group flex items-start gap-4 p-5 transition-colors"
    :class="[to ? styles.ring : '', to ? 'cursor-pointer' : '']"
  >
    <div
      v-if="icon"
      class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg"
      :class="styles.iconBg"
    >
      <component :is="icon" class="h-5 w-5" aria-hidden="true" />
    </div>

    <div class="min-w-0 flex-1">
      <p class="text-sm font-medium text-slate-500">{{ label }}</p>
      <p class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ value }}</p>
      <p v-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
    </div>

    <ArrowUpRight
      v-if="to"
      class="h-4 w-4 shrink-0 text-slate-300 transition-colors group-hover:text-slate-500"
      aria-hidden="true"
    />
  </component>
</template>
