<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ChevronDown, LogOut, Menu, User } from 'lucide-vue-next'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'

defineProps({
  open: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['open-sidebar'])

const route = useRoute()
const router = useRouter()
const { state, logout } = useAuth()
const toast = useToast()

const userMenuOpen = ref(false)
const userMenu = ref(null)
const loggingOut = ref(false)

const initials = computed(() => {
  const name = state.user?.name || 'Pengguna'
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('')
})

const pageTitle = computed(() => route.meta.title || 'Dashboard')

async function handleLogout() {
  if (loggingOut.value) {
    return
  }

  loggingOut.value = true

  try {
    await logout()
    toast.success('Berhasil keluar dari aplikasi.')
    await router.replace({ name: 'login' })
  } catch {
    toast.error('Gagal keluar. Silakan coba lagi.')
  } finally {
    loggingOut.value = false
    userMenuOpen.value = false
  }
}

function handleOutsideClick(event) {
  if (userMenu.value && !userMenu.value.contains(event.target)) {
    userMenuOpen.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', handleOutsideClick))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleOutsideClick))
</script>

<template>
  <header
    class="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8"
  >
    <div class="flex min-w-0 items-center gap-3">
      <button
        type="button"
        class="btn-icon lg:hidden"
        aria-label="Buka navigasi"
        @click="emit('open-sidebar')"
      >
        <Menu class="h-5 w-5" aria-hidden="true" />
      </button>

      <div class="min-w-0">
        <p class="truncate text-sm font-semibold text-slate-900 sm:text-base">{{ pageTitle }}</p>
        <p class="hidden text-xs text-slate-500 sm:block">Kelola persediaan barang dengan mudah</p>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <div class="hidden text-right sm:block">
        <p class="text-sm font-medium text-slate-700">{{ state.user?.name || 'Pengguna' }}</p>
        <p class="text-xs text-slate-500">{{ state.user?.email }}</p>
      </div>

      <div ref="userMenu" class="relative">
        <button
          type="button"
          class="flex items-center gap-2 rounded-lg p-1 pr-2 transition-colors hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
          :aria-expanded="userMenuOpen"
          aria-haspopup="menu"
          aria-label="Menu pengguna"
          @click="userMenuOpen = !userMenuOpen"
        >
          <span
            class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700"
          >
            {{ initials }}
          </span>
          <ChevronDown
            class="h-4 w-4 text-slate-500 transition-transform"
            :class="userMenuOpen ? 'rotate-180' : ''"
            aria-hidden="true"
          />
        </button>

        <Transition name="fade">
          <div
            v-if="userMenuOpen"
            class="absolute right-0 mt-2 w-60 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg shadow-slate-900/5"
            role="menu"
          >
            <div class="border-b border-slate-100 px-4 py-3">
              <p class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                <User class="h-4 w-4 text-slate-500" aria-hidden="true" />
                {{ state.user?.name || 'Pengguna' }}
              </p>
              <p class="mt-0.5 truncate text-xs text-slate-500">{{ state.user?.email }}</p>
            </div>
            <div class="p-1.5">
              <button
                type="button"
                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-red-50 hover:text-red-700 disabled:opacity-60"
                role="menuitem"
                :disabled="loggingOut"
                @click="handleLogout"
              >
                <LogOut class="h-4 w-4" aria-hidden="true" />
                {{ loggingOut ? 'Keluar...' : 'Keluar' }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </header>
</template>
