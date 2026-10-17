<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'md',
  },
  closable: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['update:modelValue'])
const panel = ref(null)
let previouslyFocused = null

const sizeClasses = {
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
}

const panelClass = computed(() => sizeClasses[props.size] || sizeClasses.md)

function close() {
  if (props.closable) {
    emit('update:modelValue', false)
  }
}

function onKeydown(event) {
  if (!props.modelValue) {
    return
  }

  if (event.key === 'Escape') {
    close()
    return
  }

  if (event.key !== 'Tab' || !panel.value) {
    return
  }

  const focusable = [...panel.value.querySelectorAll(
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
  )].filter((element) => element.offsetParent !== null)

  if (focusable.length === 0) {
    event.preventDefault()
    panel.value.focus()
    return
  }

  const first = focusable[0]
  const last = focusable[focusable.length - 1]

  if (event.shiftKey && (document.activeElement === first || document.activeElement === panel.value)) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

watch(
  () => props.modelValue,
  (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : ''

    if (isOpen) {
      previouslyFocused = document.activeElement
      nextTick(() => panel.value?.focus())
    } else if (previouslyFocused instanceof HTMLElement) {
      previouslyFocused.focus()
      previouslyFocused = null
    }
  },
)

document.addEventListener('keydown', onKeydown)

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-[60] flex items-end justify-center overflow-y-auto bg-slate-900/40 p-0 backdrop-blur-[2px] sm:items-center sm:p-6"
        role="dialog"
        aria-modal="true"
        :aria-label="title || 'Dialog'"
        @mousedown.self="close"
      >
        <div
          ref="panel"
          tabindex="-1"
          class="modal-panel w-full rounded-t-2xl bg-white shadow-2xl outline-none sm:rounded-xl"
          :class="panelClass"
        >
          <div
            v-if="title || $slots.header"
            class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6"
          >
            <slot name="header">
              <div>
                <h2 class="text-base font-semibold text-slate-900">{{ title }}</h2>
                <p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p>
              </div>
            </slot>
            <button
              v-if="closable"
              type="button"
              class="btn-icon btn-icon-sm -mr-1 -mt-1 shrink-0"
              aria-label="Tutup dialog"
              @click="close"
            >
              <X class="h-4 w-4" aria-hidden="true" />
            </button>
          </div>

          <div class="max-h-[calc(100vh-12rem)] overflow-y-auto px-5 py-5 sm:px-6">
            <slot />
          </div>

          <div
            v-if="$slots.footer"
            class="flex flex-col-reverse gap-2 border-t border-slate-200 px-5 py-4 sm:flex-row sm:justify-end sm:px-6"
          >
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
