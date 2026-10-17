<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterView } from 'vue-router'
import AppHeader from '../components/AppHeader.vue'
import AppSidebar from '../components/AppSidebar.vue'

const mobileOpen = ref(false)
const collapsed = ref(localStorage.getItem('stokku_sidebar_collapsed') === '1')

function toggleCollapse() {
  collapsed.value = !collapsed.value
  localStorage.setItem('stokku_sidebar_collapsed', collapsed.value ? '1' : '0')
}

watch(mobileOpen, (isOpen) => {
  document.body.style.overflow = isOpen ? 'hidden' : ''
})

onMounted(() => {
  document.title = document.title || 'Stok Ku'
})

onBeforeUnmount(() => {
  document.body.style.overflow = ''
})
</script>

<template>
  <div class="flex min-h-screen bg-slate-50">
    <AppSidebar
      :open="mobileOpen"
      :collapsed="collapsed"
      @close="mobileOpen = false"
      @toggle-collapse="toggleCollapse"
    />

    <div class="flex min-w-0 flex-1 flex-col">
      <AppHeader @open-sidebar="mobileOpen = true" />

      <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div class="mx-auto w-full max-w-7xl">
          <RouterView />
        </div>
      </main>

      <footer class="border-t border-slate-200 bg-white px-4 py-3.5 sm:px-6 lg:px-8">
        <p class="text-center text-xs text-slate-500">
          Stok Ku &copy; {{ new Date().getFullYear() }}
        </p>
      </footer>
    </div>
  </div>
</template>
