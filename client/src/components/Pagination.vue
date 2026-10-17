<script setup>
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
  meta: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:page'])

const currentPage = computed(() => props.meta?.current_page || 1)
const lastPage = computed(() => props.meta?.last_page || 1)

const pages = computed(() => {
  const last = lastPage.value
  const current = currentPage.value

  if (last <= 7) {
    return Array.from({ length: last }, (_, index) => index + 1)
  }

  const items = [1]

  if (current > 4) {
    items.push('...')
  }

  const start = Math.max(2, current - 1)
  const end = Math.min(last - 1, current + 1)

  for (let page = start; page <= end; page++) {
    items.push(page)
  }

  if (current < last - 3) {
    items.push('...')
  }

  items.push(last)

  return items
})

function goTo(page) {
  if (page === '...' || page < 1 || page > lastPage.value || page === currentPage.value) {
    return
  }

  emit('update:page', page)
}
</script>

<template>
  <div
    class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
  >
    <p class="text-xs text-slate-500 sm:text-sm">
      <template v-if="meta.total > 0">
        Menampilkan <span class="font-medium text-slate-700">{{ meta.from }}–{{ meta.to }}</span> dari
        <span class="font-medium text-slate-700">{{ meta.total }}</span> data
      </template>
      <template v-else>Tidak ada data untuk ditampilkan</template>
    </p>

    <nav v-if="lastPage > 1" class="flex items-center gap-1" aria-label="Navigasi halaman">
      <button
        type="button"
        class="btn-icon-sm"
        :disabled="currentPage === 1"
        aria-label="Halaman sebelumnya"
        @click="goTo(currentPage - 1)"
      >
        <ChevronLeft class="h-4 w-4" aria-hidden="true" />
      </button>

      <template v-for="(page, index) in pages" :key="`${page}-${index}`">
        <span v-if="page === '...'" class="px-1.5 text-xs text-slate-500">...</span>
        <button
          v-else
          type="button"
          class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-xs font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
          :class="
            page === currentPage
              ? 'bg-indigo-600 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          "
          :aria-current="page === currentPage ? 'page' : undefined"
          @click="goTo(page)"
        >
          {{ page }}
        </button>
      </template>

      <button
        type="button"
        class="btn-icon-sm"
        :disabled="currentPage === lastPage"
        aria-label="Halaman berikutnya"
        @click="goTo(currentPage + 1)"
      >
        <ChevronRight class="h-4 w-4" aria-hidden="true" />
      </button>
    </nav>
  </div>
</template>
