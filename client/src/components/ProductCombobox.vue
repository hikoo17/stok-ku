<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId } from 'vue'
import { ChevronDown, Search, X } from 'lucide-vue-next'
import { formatNumber } from '../composables/useFormat'

const props = defineProps({
  id: {
    type: String,
    default: undefined,
  },
  modelValue: {
    type: [String, Number, null],
    default: '',
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: 'Cari atau pilih barang...',
  },
  invalid: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'select'])

const root = ref(null)
const input = ref(null)
const isOpen = ref(false)
const query = ref('')
const highlightedIndex = ref(-1)
const listId = useId()
const generatedInputId = useId()
const inputId = computed(() => props.id || generatedInputId)

const selectedProduct = computed(
  () => props.options.find((product) => Number(product.id) === Number(props.modelValue)) || null,
)

const filteredOptions = computed(() => {
  const keyword = query.value.trim().toLowerCase()

  if (!keyword) {
    return props.options
  }

  return props.options.filter((product) => {
    return (
      String(product.sku).toLowerCase().includes(keyword) ||
      String(product.name).toLowerCase().includes(keyword)
    )
  })
})

const selectedLabel = computed(() =>
  selectedProduct.value
    ? `${selectedProduct.value.sku} — ${selectedProduct.value.name}`
    : '',
)

const displayValue = computed(() => (isOpen.value ? query.value : selectedLabel.value))

function productOptionLabel(product) {
  return `${product.sku} — ${product.name}`
}

function formatStock(product) {
  return `Stok ${formatNumber(product.current_stock)} ${product.unit?.abbreviation || ''}`.trim()
}

function openList() {
  isOpen.value = true
  query.value = ''
  const selectedIndex = filteredOptions.value.findIndex(
    (product) => Number(product.id) === Number(props.modelValue),
  )
  highlightedIndex.value = selectedIndex >= 0 ? selectedIndex : 0
}

function closeList() {
  isOpen.value = false
  query.value = ''
  highlightedIndex.value = -1
}

function choose(product) {
  emit('update:modelValue', product.id)
  emit('select', product)
  closeList()
}

function clearSelection() {
  emit('update:modelValue', '')
  emit('select', null)
  query.value = ''
  nextTick(() => input.value?.focus())
}

function onInput(event) {
  query.value = event.target.value
  isOpen.value = true
  highlightedIndex.value = filteredOptions.value.length > 0 ? 0 : -1
}

function moveHighlight(step) {
  if (!isOpen.value) {
    openList()
    return
  }

  const total = filteredOptions.value.length

  if (total === 0) {
    return
  }

  highlightedIndex.value = (highlightedIndex.value + step + total) % total
  scrollHighlightedIntoView()
}

function scrollHighlightedIntoView() {
  nextTick(() => {
    document.getElementById(`${listId}-option-${highlightedIndex.value}`)?.scrollIntoView({
      block: 'nearest',
    })
  })
}

function onKeydown(event) {
  if (event.key === 'ArrowDown') {
    event.preventDefault()
    moveHighlight(1)
    return
  }

  if (event.key === 'ArrowUp') {
    event.preventDefault()
    moveHighlight(-1)
    return
  }

  if (event.key === 'Enter') {
    if (isOpen.value && filteredOptions.value[highlightedIndex.value]) {
      event.preventDefault()
      choose(filteredOptions.value[highlightedIndex.value])
    }
    return
  }

  if (event.key === 'Escape') {
    closeList()
    input.value?.blur()
    return
  }

  if (event.key === 'Tab') {
    closeList()
  }
}

function onFocusOut(event) {
  if (!root.value?.contains(event.relatedTarget)) {
    closeList()
  }
}

function onDocumentClick(event) {
  if (isOpen.value && root.value && !root.value.contains(event.target)) {
    closeList()
  }
}

onMounted(() => document.addEventListener('mousedown', onDocumentClick))
onBeforeUnmount(() => document.removeEventListener('mousedown', onDocumentClick))
</script>

<template>
  <div ref="root" class="relative" @focusout="onFocusOut">
    <div class="relative">
      <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
        <Search class="h-4 w-4" aria-hidden="true" />
      </span>
      <input
        :id="inputId"
        ref="input"
        :value="displayValue"
        type="text"
        role="combobox"
        autocomplete="off"
        class="field-input pl-9 pr-16"
        :class="{ 'field-input-error': invalid }"
        :placeholder="placeholder"
        :aria-expanded="isOpen"
        :aria-controls="listId"
        aria-autocomplete="list"
        :aria-activedescendant="
          isOpen && highlightedIndex >= 0 ? `${listId}-option-${highlightedIndex}` : undefined
        "
        :aria-invalid="invalid"
        @focus="openList"
        @input="onInput"
        @keydown="onKeydown"
      />
      <div class="absolute inset-y-0 right-0 flex items-center pr-1">
        <button
          v-if="selectedProduct && !isOpen"
          type="button"
          class="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
          aria-label="Hapus pilihan barang"
          @click="clearSelection"
        >
          <X class="h-4 w-4" aria-hidden="true" />
        </button>
        <span v-else class="flex h-8 w-8 items-center justify-center text-slate-500 pointer-events-none">
          <ChevronDown class="h-4 w-4" :class="isOpen ? 'rotate-180' : ''" aria-hidden="true" />
        </span>
      </div>
    </div>

    <ul
      v-if="isOpen"
      :id="listId"
      role="listbox"
      class="absolute z-30 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg shadow-slate-900/5"
    >
      <li
        v-for="(product, index) in filteredOptions"
        :id="`${listId}-option-${index}`"
        :key="product.id"
        role="option"
        :aria-selected="Number(product.id) === Number(modelValue)"
        class="cursor-pointer px-3 py-2.5 transition-colors"
        :class="index === highlightedIndex ? 'bg-indigo-50' : 'hover:bg-slate-50'"
        @mousedown.prevent="choose(product)"
        @mouseenter="highlightedIndex = index"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="truncate text-sm font-medium text-slate-800">{{ productOptionLabel(product) }}</p>
            <p class="mt-0.5 text-xs text-slate-500">
              {{ product.category?.name || '-' }} &middot; {{ product.unit?.name || '-' }}
            </p>
          </div>
          <span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
            {{ formatStock(product) }}
          </span>
        </div>
      </li>

      <li v-if="filteredOptions.length === 0" class="px-3 py-4 text-center text-sm text-slate-500">
        Barang tidak ditemukan.
      </li>
    </ul>
  </div>
</template>
