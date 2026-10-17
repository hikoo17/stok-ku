<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  ArrowDownToLine,
  ArrowUpFromLine,
  ChevronLeft,
  FileText,
  History,
  LayoutDashboard,
  Package,
  Ruler,
  Tags,
  X,
} from 'lucide-vue-next'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'toggle-collapse'])
const route = useRoute()

const groups = [
  {
    label: 'Utama',
    items: [
      { label: 'Dashboard', to: '/', icon: LayoutDashboard, exact: true },
      { label: 'Data Barang', to: '/products', icon: Package },
      { label: 'Kategori', to: '/categories', icon: Tags },
      { label: 'Satuan', to: '/units', icon: Ruler },
    ],
  },
  {
    label: 'Transaksi',
    items: [
      { label: 'Barang Masuk', to: '/stock-in', icon: ArrowDownToLine },
      { label: 'Barang Keluar', to: '/stock-out', icon: ArrowUpFromLine },
      { label: 'Riwayat Stok', to: '/history', icon: History },
    ],
  },
  {
    label: 'Laporan',
    items: [{ label: 'Laporan', to: '/reports', icon: FileText }],
  },
]

const currentPath = computed(() => route.path)

function isActive(item) {
  if (item.exact) {
    return currentPath.value === item.to
  }

  return currentPath.value === item.to || currentPath.value.startsWith(`${item.to}/`)
}
</script>

<template>
  <!-- Desktop sidebar -->
  <aside
    class="sticky top-0 hidden h-screen shrink-0 flex-col border-r border-slate-200 bg-white transition-[width] duration-200 lg:flex"
    :class="collapsed ? 'w-20' : 'w-64'"
  >
    <div class="flex h-16 items-center gap-3 border-b border-slate-100 px-4" :class="collapsed ? 'justify-center' : 'px-5'">
      <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-sm">
        <Package class="h-5 w-5" aria-hidden="true" />
      </div>
      <div v-if="!collapsed" class="min-w-0">
        <p class="truncate text-sm font-semibold text-slate-900">Stok Ku</p>
        <p class="truncate text-[11px] text-slate-500">Manajemen Persediaan</p>
      </div>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5" aria-label="Navigasi utama">
      <div v-for="group in groups" :key="group.label">
        <p
          v-if="!collapsed"
          class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500"
        >
          {{ group.label }}
        </p>
        <div class="space-y-1">
          <RouterLink
            v-for="item in group.items"
            :key="item.to"
            :to="item.to"
            class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
            :class="[
              isActive(item)
                ? 'bg-indigo-50 text-indigo-700'
                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900',
              collapsed ? 'justify-center' : '',
            ]"
            :title="collapsed ? item.label : undefined"
            :aria-current="isActive(item) ? 'page' : undefined"
          >
            <component
              :is="item.icon"
              class="h-5 w-5 shrink-0"
              :class="isActive(item) ? 'text-indigo-600' : 'text-slate-500 group-hover:text-slate-600'"
              aria-hidden="true"
            />
            <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
          </RouterLink>
        </div>
      </div>
    </nav>

    <div class="border-t border-slate-100 p-3">
      <button
        type="button"
        class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
        :class="collapsed ? 'justify-center' : ''"
        :aria-label="collapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'"
        @click="emit('toggle-collapse')"
      >
        <ChevronLeft
          class="h-5 w-5 shrink-0 transition-transform"
          :class="collapsed ? 'rotate-180' : ''"
          aria-hidden="true"
        />
        <span v-if="!collapsed">Ciutkan</span>
      </button>
      <p v-if="!collapsed" class="px-3 pt-2 text-[11px] text-slate-500">Stok Ku v1.0</p>
    </div>
  </aside>

  <!-- Mobile drawer -->
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-[2px] lg:hidden"
        aria-hidden="true"
        @click="emit('close')"
      />
    </Transition>
    <Transition name="modal">
      <aside
        v-if="open"
        class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-white shadow-2xl lg:hidden"
        role="dialog"
        aria-modal="true"
        aria-label="Navigasi seluler"
      >
        <div class="flex h-16 items-center justify-between border-b border-slate-100 px-5">
          <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
              <Package class="h-5 w-5" aria-hidden="true" />
            </div>
            <div>
              <p class="text-sm font-semibold text-slate-900">Stok Ku</p>
              <p class="text-[11px] text-slate-500">Manajemen Persediaan</p>
            </div>
          </div>
          <button type="button" class="btn-icon h-8 w-8" aria-label="Tutup navigasi" @click="emit('close')">
            <X class="h-4 w-4" aria-hidden="true" />
          </button>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5" aria-label="Navigasi utama seluler">
          <div v-for="group in groups" :key="group.label">
            <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              {{ group.label }}
            </p>
            <div class="space-y-1">
              <RouterLink
                v-for="item in group.items"
                :key="item.to"
                :to="item.to"
                class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                :class="
                  isActive(item)
                    ? 'bg-indigo-50 text-indigo-700'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                "
                @click="emit('close')"
              >
                <component
                  :is="item.icon"
                  class="h-5 w-5 shrink-0"
                  :class="isActive(item) ? 'text-indigo-600' : 'text-slate-500'"
                  aria-hidden="true"
                />
                {{ item.label }}
              </RouterLink>
            </div>
          </div>
        </nav>

        <div class="border-t border-slate-100 p-4">
          <p class="text-[11px] text-slate-500">Stok Ku v1.0</p>
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>
