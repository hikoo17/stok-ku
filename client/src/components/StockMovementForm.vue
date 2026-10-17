<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  ArrowDownToLine,
  ArrowUpFromLine,
  CheckCircle2,
  History,
  Info,
  Package,
  Save,
  TriangleAlert,
} from 'lucide-vue-next'
import PageHeader from './PageHeader.vue'
import FormField from './FormField.vue'
import Skeleton from './Skeleton.vue'
import LoadingSpinner from './LoadingSpinner.vue'
import ErrorState from './ErrorState.vue'
import EmptyState from './EmptyState.vue'
import StatusBadge from './StatusBadge.vue'
import { productService } from '../services/products'
import { transactionService } from '../services/transactions'
import { useToast } from '../composables/useToast'
import { getErrorMessage, getValidationErrors } from '../composables/useApiError'
import {
  formatCurrency,
  formatNumber,
  todayInput,
} from '../composables/useFormat'

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (value) => ['in', 'out'].includes(value),
  },
})

const route = useRoute()
const toast = useToast()

const isIncoming = computed(() => props.type === 'in')

const config = computed(() =>
  isIncoming.value
    ? {
        title: 'Barang Masuk',
        description: 'Catat penerimaan barang.',
        priceLabel: 'Harga Beli',
        descriptionLabel: 'Pemasok / Sumber',
        descriptionPlaceholder: 'Contoh: PT Sinar Parts',
        submitLabel: 'Simpan Barang Masuk',
        icon: ArrowDownToLine,
        accent: 'emerald',
        endpoint: (payload) => transactionService.stockIn(payload),
      }
    : {
        title: 'Barang Keluar',
        description: 'Catat pengeluaran barang.',
        priceLabel: 'Harga Jual',
        descriptionLabel: 'Tujuan / Penerima',
        descriptionPlaceholder: 'Contoh: Bengkel Jaya Motor',
        submitLabel: 'Simpan Barang Keluar',
        icon: ArrowUpFromLine,
        accent: 'sky',
        endpoint: (payload) => transactionService.stockOut(payload),
      },
)

const products = ref([])
const loading = ref(true)
const loadError = ref('')
const submitting = ref(false)
const formError = ref('')
const errors = reactive({})
const lastTransaction = ref(null)
const idempotencyKey = ref(createUuid())

const form = reactive({
  product_id: '',
  quantity: '',
  unit_price: '',
  transaction_date: todayInput(),
  description: '',
  notes: '',
})

const selectedProduct = computed(
  () => products.value.find((product) => Number(product.id) === Number(form.product_id)) || null,
)

const quantityValue = computed(() => Number(form.quantity) || 0)
const unitPriceValue = computed(() => Number(form.unit_price) || 0)

const stockBefore = computed(() => Number(selectedProduct.value?.current_stock) || 0)
const stockAfter = computed(() => {
  const after = isIncoming.value ? stockBefore.value + quantityValue.value : stockBefore.value - quantityValue.value

  return Math.round(after * 1000) / 1000
})
const isInsufficient = computed(() => !isIncoming.value && quantityValue.value > stockBefore.value)
const totalValue = computed(() => quantityValue.value * unitPriceValue.value)

const productOptions = computed(() =>
  products.value.map((product) => ({
    value: product.id,
    label: `${product.sku} — ${product.name} (stok: ${formatNumber(product.current_stock)} ${product.unit?.abbreviation || ''})`,
  })),
)

function createUuid() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }

  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
    const random = (Math.random() * 16) | 0
    const value = character === 'x' ? random : (random & 0x3) | 0x8

    return value.toString(16)
  })
}

function clearErrors() {
  Object.keys(errors).forEach((key) => delete errors[key])
  formError.value = ''
}

function defaultPriceForProduct(product) {
  if (!product) {
    return ''
  }

  return isIncoming.value ? product.purchase_price : product.selling_price
}

function applyProductSelection() {
  const product = selectedProduct.value
  form.unit_price = defaultPriceForProduct(product)
  delete errors.product_id
}

async function loadProducts(options = {}) {
  const { silent = false } = options

  if (!silent) {
    loading.value = true
  }

  loadError.value = ''

  try {
    const response = await productService.options({ limit: 500 })
    products.value = response.data.data

    const requestedId = Number(route.query.product_id)
    const preselected = products.value.find((product) => Number(product.id) === requestedId)

    if (preselected) {
      form.product_id = preselected.id
      form.unit_price = defaultPriceForProduct(preselected)
    }
  } catch (requestError) {
    if (!silent) {
      loadError.value = getErrorMessage(requestError, 'Daftar barang gagal dimuat.')
    }
  } finally {
    if (!silent) {
      loading.value = false
    }
  }
}

function validate() {
  clearErrors()

  if (!form.product_id) {
    errors.product_id = 'Barang wajib dipilih.'
  }

  if (!form.quantity || quantityValue.value <= 0) {
    errors.quantity = 'Jumlah harus lebih besar dari nol.'
  } else if (isInsufficient.value) {
    errors.quantity = `Jumlah keluar melebihi stok tersedia (${formatNumber(stockBefore.value)} ${selectedProduct.value?.unit?.abbreviation || ''}).`
  }

  if (form.unit_price === '' || !Number.isFinite(unitPriceValue.value) || unitPriceValue.value < 0) {
    errors.unit_price = 'Harga harus berupa angka nol atau lebih.'
  }

  if (!form.transaction_date) {
    errors.transaction_date = 'Tanggal transaksi wajib diisi.'
  } else if (form.transaction_date > todayInput()) {
    errors.transaction_date = 'Tanggal transaksi tidak boleh melebihi hari ini.'
  }

  return Object.keys(errors).length === 0
}

async function submit() {
  if (submitting.value || !validate()) {
    return
  }

  submitting.value = true

  try {
    const response = await config.value.endpoint({
      product_id: Number(form.product_id),
      quantity: quantityValue.value,
      unit_price: unitPriceValue.value,
      transaction_date: form.transaction_date,
      description: form.description.trim() || null,
      notes: form.notes.trim() || null,
      idempotency_key: idempotencyKey.value,
    })

    lastTransaction.value = response.data.data
    toast.success(response.data.message || 'Transaksi berhasil disimpan.')

    const currentProductId = form.product_id
    form.quantity = ''
    form.description = ''
    form.notes = ''
    idempotencyKey.value = createUuid()
    clearErrors()

    await loadProducts({ silent: true })

    const refreshedProduct = products.value.find((product) => Number(product.id) === Number(currentProductId))
    form.product_id = refreshedProduct?.id || ''
    form.unit_price = defaultPriceForProduct(refreshedProduct)
  } catch (requestError) {
    Object.assign(errors, getValidationErrors(requestError))
    formError.value = getErrorMessage(requestError, 'Transaksi gagal disimpan. Periksa kembali data yang diisi.')
  } finally {
    submitting.value = false
  }
}

function resetForm() {
  const product = selectedProduct.value
  form.quantity = ''
  form.description = ''
  form.notes = ''
  form.transaction_date = todayInput()
  form.unit_price = defaultPriceForProduct(product)
  idempotencyKey.value = createUuid()
  lastTransaction.value = null
  clearErrors()
}

onMounted(loadProducts)
</script>

<template>
  <div>
    <PageHeader :title="config.title" :description="config.description">
      <template #actions>
        <RouterLink to="/history" class="btn btn-secondary">
          <History class="h-4 w-4" aria-hidden="true" />
          Riwayat Stok
        </RouterLink>
        <RouterLink
          :to="isIncoming ? '/stock-out' : '/stock-in'"
          class="btn btn-secondary"
        >
          <component :is="isIncoming ? ArrowUpFromLine : ArrowDownToLine" class="h-4 w-4" aria-hidden="true" />
          {{ isIncoming ? 'Barang Keluar' : 'Barang Masuk' }}
        </RouterLink>
      </template>
    </PageHeader>

    <div v-if="loading" class="grid grid-cols-1 gap-6 lg:grid-cols-3" role="status" aria-label="Memuat data barang">
      <div class="space-y-6 lg:col-span-2">
        <div class="card">
          <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <Skeleton width="12rem" height="1rem" />
          </div>
          <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
            <div v-for="field in 7" :key="field" class="space-y-2" :class="field === 1 || field === 6 ? 'sm:col-span-2' : ''">
              <Skeleton width="30%" height="0.7rem" />
              <Skeleton :height="field === 6 ? '5.5rem' : '2.5rem'" class="rounded-lg" />
            </div>
          </div>
          <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4 sm:px-6">
            <Skeleton width="5rem" height="2.5rem" class="rounded-lg" />
            <Skeleton width="9rem" height="2.5rem" class="rounded-lg" />
          </div>
        </div>
      </div>
      <div class="space-y-6">
        <div class="card p-5 sm:p-6">
          <Skeleton width="6rem" height="1rem" />
          <Skeleton width="80%" height="0.7rem" class="mt-2" />
          <div class="mt-5 space-y-4">
            <div v-for="row in 4" :key="row" class="flex items-center justify-between gap-4">
              <Skeleton width="40%" height="0.75rem" />
              <Skeleton width="30%" height="0.9rem" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="loadError" class="card">
      <ErrorState :message="loadError" @retry="loadProducts" />
    </div>

    <div v-else-if="products.length === 0" class="card">
      <EmptyState
        :icon="Package"
        title="Belum ada barang aktif"
        message="Tambahkan barang terlebih dahulu sebelum mencatat transaksi stok."
      >
        <template #action>
          <RouterLink to="/products/create" class="btn btn-primary">Tambah Barang</RouterLink>
        </template>
      </EmptyState>
    </div>

    <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <div
          v-if="lastTransaction"
          class="flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
          role="status"
        >
          <div class="flex items-start gap-3">
            <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" aria-hidden="true" />
            <div>
              <p class="text-sm font-semibold text-emerald-900">Transaksi berhasil disimpan</p>
              <p class="mt-0.5 text-xs text-emerald-700">
                {{ lastTransaction.transaction_number }} &middot; stok terbaru
                {{ formatNumber(lastTransaction.stock_after) }}
                {{ lastTransaction.product?.unit?.abbreviation }}
              </p>
            </div>
          </div>
          <RouterLink
            :to="{ name: 'history', query: { search: lastTransaction.transaction_number } }"
            class="btn btn-secondary shrink-0 self-start sm:self-auto"
          >
            Lihat Riwayat
          </RouterLink>
        </div>

        <div
          v-if="formError"
          class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
          role="alert"
        >
          {{ formError }}
        </div>

        <form class="card" novalidate @submit.prevent="submit">
          <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-2.5">
              <span
                class="flex h-9 w-9 items-center justify-center rounded-lg"
                :class="isIncoming ? 'bg-emerald-50 text-emerald-600' : 'bg-sky-50 text-sky-600'"
              >
                <component :is="config.icon" class="h-5 w-5" aria-hidden="true" />
              </span>
              <div>
                <h2 class="section-title">Informasi Transaksi</h2>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
            <div class="sm:col-span-2">
              <FormField
                label="Barang"
                for-id="movement-product"
                required
                :error="errors.product_id"
                hint="Hanya barang aktif yang dapat dipilih."
              >
                <select
                  id="movement-product"
                  v-model="form.product_id"
                  class="field-input cursor-pointer"
                  :class="{ 'field-input-error': errors.product_id }"
                  @change="applyProductSelection"
                >
                  <option value="" disabled>Pilih barang</option>
                  <option v-for="option in productOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </FormField>
            </div>

            <FormField
              label="Jumlah"
              for-id="movement-quantity"
              required
              :error="errors.quantity"
              :hint="isIncoming ? '' : `Stok tersedia: ${formatNumber(stockBefore)} ${selectedProduct?.unit?.abbreviation || ''}.`"
            >
              <div class="relative">
                <input
                  id="movement-quantity"
                  v-model="form.quantity"
                  type="number"
                  min="0.001"
                  step="0.001"
                  class="field-input pr-14 text-right"
                  :class="{ 'field-input-error': errors.quantity }"
                  placeholder="0"
                  :aria-invalid="Boolean(errors.quantity)"
                />
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-medium text-slate-500">
                  {{ selectedProduct?.unit?.abbreviation || 'unit' }}
                </span>
              </div>
            </FormField>

            <FormField
              :label="config.priceLabel"
              for-id="movement-price"
              required
              :error="errors.unit_price"
              :hint="
                isIncoming
                  ? 'Nilai awal dari harga beli. Dapat diubah.'
                  : 'Nilai awal dari harga jual. Dapat diubah.'
              "
            >
              <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-medium text-slate-500">
                  Rp
                </span>
                <input
                  id="movement-price"
                  v-model="form.unit_price"
                  type="number"
                  min="0"
                  step="0.01"
                  class="field-input pl-9 text-right"
                  :class="{ 'field-input-error': errors.unit_price }"
                  placeholder="0"
                />
              </div>
            </FormField>

            <FormField
              label="Tanggal Transaksi"
              for-id="movement-date"
              required
              :error="errors.transaction_date"
            >
              <input
                id="movement-date"
                v-model="form.transaction_date"
                type="date"
                class="field-input"
                :class="{ 'field-input-error': errors.transaction_date }"
                :max="todayInput()"
              />
            </FormField>

            <FormField
              :label="config.descriptionLabel"
              for-id="movement-description"
              :error="errors.description"
            >
              <input
                id="movement-description"
                v-model="form.description"
                type="text"
                maxlength="255"
                class="field-input"
                :class="{ 'field-input-error': errors.description }"
                :placeholder="config.descriptionPlaceholder"
              />
            </FormField>

            <div class="sm:col-span-2">
              <FormField label="Catatan" for-id="movement-notes" :error="errors.notes">
                <textarea
                  id="movement-notes"
                  v-model="form.notes"
                  rows="3"
                  maxlength="1000"
                  class="field-input resize-y"
                  :class="{ 'field-input-error': errors.notes }"
                  placeholder="Catatan tambahan mengenai transaksi ini..."
                />
              </FormField>
            </div>
          </div>

          <div class="flex flex-col-reverse gap-2 border-t border-slate-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
            <button type="button" class="btn btn-secondary" :disabled="submitting" @click="resetForm">
              Reset
            </button>
            <button
              type="submit"
              class="btn"
              :class="isIncoming ? 'btn-success' : 'btn-primary'"
              :disabled="submitting"
            >
              <LoadingSpinner v-if="submitting" />
              <Save v-else class="h-4 w-4" aria-hidden="true" />
              {{ submitting ? 'Menyimpan...' : config.submitLabel }}
            </button>
          </div>
        </form>
      </div>

      <div class="space-y-6">
        <section class="card p-5 sm:p-6 lg:sticky lg:top-24">
          <h2 class="section-title">Ringkasan</h2>
          <p class="mt-1 text-sm text-slate-500">Perkiraan perubahan stok.</p>

          <div v-if="selectedProduct" class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ selectedProduct.sku }}</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ selectedProduct.name }}</p>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
              <span>{{ selectedProduct.category?.name }}</span>
              <span class="text-slate-300">•</span>
              <span>{{ selectedProduct.unit?.name }} ({{ selectedProduct.unit?.abbreviation }})</span>
            </div>
            <div class="mt-3">
              <StatusBadge :status="selectedProduct.stock_status" />
            </div>
          </div>

          <dl v-if="selectedProduct" class="mt-5 space-y-3 text-sm">
            <div class="flex items-center justify-between gap-4">
              <dt class="text-slate-500">Stok Saat Ini</dt>
              <dd class="font-medium text-slate-900">
                {{ formatNumber(stockBefore) }}
                <span class="text-xs font-normal text-slate-500">{{ selectedProduct?.unit?.abbreviation }}</span>
              </dd>
            </div>
            <div class="flex items-center justify-between gap-4">
              <dt class="text-slate-500">{{ isIncoming ? 'Jumlah Masuk' : 'Jumlah Keluar' }}</dt>
              <dd class="font-medium" :class="isIncoming ? 'text-emerald-700' : 'text-sky-700'">
                {{ isIncoming ? '+' : '-' }}{{ formatNumber(quantityValue) }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
              <dt class="font-medium text-slate-700">Stok Setelah</dt>
              <dd class="text-base font-semibold" :class="stockAfter < 0 ? 'text-red-600' : 'text-slate-900'">
                {{ formatNumber(stockAfter) }}
                <span class="text-xs font-normal text-slate-500">{{ selectedProduct?.unit?.abbreviation }}</span>
              </dd>
            </div>
          </dl>

          <p v-if="isInsufficient" class="mt-3 flex items-start gap-1.5 text-xs font-medium text-red-600" role="alert">
            <TriangleAlert class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            <span>Jumlah keluar melebihi stok yang tersedia.</span>
          </p>

          <div v-if="selectedProduct" class="mt-5 space-y-3 border-t border-slate-200 pt-4 text-sm">
            <div class="flex items-center justify-between gap-4">
              <span class="text-slate-500">{{ config.priceLabel }}</span>
              <span class="font-medium text-slate-900">{{ formatCurrency(unitPriceValue) }}</span>
            </div>
            <div class="flex items-center justify-between gap-4">
              <span class="text-slate-500">Total Nilai</span>
              <span class="font-semibold text-slate-900">{{ formatCurrency(totalValue) }}</span>
            </div>
          </div>
          <p v-else class="mt-4 rounded-lg border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
            Pilih barang untuk melihat ringkasan.
          </p>

          <div class="mt-5 flex items-start gap-2 rounded-lg bg-indigo-50/70 p-3 text-xs leading-5 text-indigo-800">
            <Info class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
            <p>Stok diperbarui otomatis saat transaksi disimpan.</p>
          </div>
        </section>

        <section v-if="lastTransaction" class="card p-5 sm:p-6">
          <div class="flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
              <CheckCircle2 class="h-4 w-4" aria-hidden="true" />
            </span>
            <div class="min-w-0">
              <h2 class="text-sm font-semibold text-slate-900">Transaksi terakhir</h2>
              <p class="mt-1 truncate font-mono text-xs text-slate-500">{{ lastTransaction.transaction_number }}</p>
              <p class="mt-2 text-xs text-slate-500">
                {{ lastTransaction.product?.name }} &middot;
                {{ lastTransaction.transaction_type === 'in' ? 'Masuk' : 'Keluar' }}
                {{ formatNumber(lastTransaction.quantity) }} {{ lastTransaction.product?.unit?.abbreviation }}
              </p>
              <p class="mt-1 text-xs text-slate-500">
                Stok akhir: {{ formatNumber(lastTransaction.stock_after) }}
              </p>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>
