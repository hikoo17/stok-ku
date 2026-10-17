<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    default: null,
  },
})

const mapping = {
  in_stock: { label: 'Stok Aman', classes: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' },
  low_stock: { label: 'Stok Menipis', classes: 'bg-amber-50 text-amber-700 ring-amber-600/20' },
  out_of_stock: { label: 'Stok Habis', classes: 'bg-red-50 text-red-700 ring-red-600/20' },
  active: { label: 'Aktif', classes: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' },
  inactive: { label: 'Nonaktif', classes: 'bg-slate-100 text-slate-600 ring-slate-500/20' },
  in: { label: 'Barang Masuk', classes: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' },
  out: { label: 'Barang Keluar', classes: 'bg-sky-50 text-sky-700 ring-sky-600/20' },
  success: { label: 'Berhasil', classes: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' },
  warning: { label: 'Perhatian', classes: 'bg-amber-50 text-amber-700 ring-amber-600/20' },
  danger: { label: 'Bahaya', classes: 'bg-red-50 text-red-700 ring-red-600/20' },
  neutral: { label: 'Netral', classes: 'bg-slate-100 text-slate-600 ring-slate-500/20' },
}

const config = computed(() => mapping[props.status] || mapping.neutral)
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
    :class="config.classes"
  >
    {{ label || config.label }}
  </span>
</template>
