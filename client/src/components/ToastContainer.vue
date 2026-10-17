<script setup>
import { CheckCircle2, CircleAlert, Info, X } from 'lucide-vue-next'
import { useToast } from '../composables/useToast'

const { toasts, removeToast } = useToast()

const styles = {
  success: {
    wrapper: 'border-emerald-200 bg-white',
    icon: 'text-emerald-600',
    component: CheckCircle2,
  },
  error: {
    wrapper: 'border-red-200 bg-white',
    icon: 'text-red-600',
    component: CircleAlert,
  },
  info: {
    wrapper: 'border-slate-200 bg-white',
    icon: 'text-indigo-600',
    component: Info,
  },
}
</script>

<template>
  <Teleport to="body">
    <div
      class="pointer-events-none fixed inset-x-4 top-4 z-[80] flex flex-col items-end gap-2 sm:inset-x-auto sm:right-5 sm:top-5"
      aria-live="polite"
      aria-atomic="false"
    >
      <TransitionGroup name="toast" tag="div" class="flex w-full flex-col items-end gap-2">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border px-4 py-3.5 shadow-lg shadow-slate-900/5"
          :class="styles[toast.type]?.wrapper || styles.info.wrapper"
          role="status"
        >
          <component
            :is="styles[toast.type]?.component || Info"
            class="mt-0.5 h-5 w-5 shrink-0"
            :class="styles[toast.type]?.icon || styles.info.icon"
            aria-hidden="true"
          />
          <p class="flex-1 text-sm font-medium leading-5 text-slate-700">{{ toast.message }}</p>
          <button
            type="button"
            class="rounded-md p-0.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
            aria-label="Tutup notifikasi"
            @click="removeToast(toast.id)"
          >
            <X class="h-4 w-4" aria-hidden="true" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
