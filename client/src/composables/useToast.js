import { reactive } from 'vue'

const toasts = reactive([])
const timers = new Map()
let nextId = 1

function removeToast(id) {
  const index = toasts.findIndex((toast) => toast.id === id)

  if (index !== -1) {
    toasts.splice(index, 1)
  }

  if (timers.has(id)) {
    clearTimeout(timers.get(id))
    timers.delete(id)
  }
}

function pushToast(message, type = 'info', duration = 4500) {
  const id = nextId++
  toasts.push({ id, message, type })

  if (duration > 0) {
    timers.set(id, setTimeout(() => removeToast(id), duration))
  }

  return id
}

export function useToast() {
  return {
    toasts,
    removeToast,
    success: (message) => pushToast(message, 'success'),
    error: (message) => pushToast(message, 'error', 6000),
    info: (message) => pushToast(message, 'info'),
  }
}
