<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  ArrowDownToLine,
  ArrowUpFromLine,
  History,
  RefreshCw,
  RotateCcw,
  SearchX,
} from 'lucide-vue-next'
import PageHeader from '../components/PageHeader.vue'
import SearchInput from '../components/SearchInput.vue'
import FilterDropdown from '../components/FilterDropdown.vue'
import DateRangePicker from '../components/DateRangePicker.vue'
import DataTable from '../components/DataTable.vue'
import Pagination from '../components/Pagination.vue'
import StatusBadge from '../components/StatusBadge.vue'
import EmptyState from '../components/EmptyState.vue'
import ErrorState from '../components/ErrorState.vue'
import { transactionService } from '../services/transactions'
import { productService } from '../services/products'
import { getErrorMessage, getValidationErrors } from '../composables/useApiError'
import { formatCurrency, formatTransactionDateTime, formatNumber, todayInput } from '../composables/useFormat'

const route = useRoute()

const columns = [
  { key: 'transaction_date', label: 'Tanggal & Waktu' },
  { key: 'transaction_number', label: 'Referensi' },
  { key: 'product', label: 'Barang' },
  { key: 'transaction_type', label: 'Jenis' },
  { key: 'quantity', label: 'Jumlah', align: 'right' },
  { key: 'stock', label: 'Stok Sebelum → Sesudah', align: 'right' },
  { key: 'unit_price', label: 'Harga', align: 'right' },
  { key: 'description', label: 'Keterangan' },
  { key: 'created_by', label: 'Dicatat Oleh' },
]

const filters = reactive({
  search: typeof route.query.search === 'string' ? route.query.search : '',
  product_id: route.query.product_id ? Number(route.query.product_id) : '',
  transaction_type:
    typeof route.query.transaction_type === 'string' ? route.query.transaction_type : '',
  date_from: '',
  date_to: '',
  sort_direction: 'desc',
  page: 1,
})

const transactions = ref([])
const products = ref([])
const meta = ref({ total: 0, current_page: 1, last_page: 1, from: 0, to: 0 })
const loading = ref(true)
const error = ref('')
const filterError = ref('')
let filterTimeout = null

const typeOptions = [
  { value: '', label: 'Semua Jenis' },
  { value: 'in', label: 'Barang Masuk' },
  { value: 'out', label: 'Barang Keluar' },
]

const sortOptions = [
  { value: 'desc', label: 'Terbaru' },
  { value: 'asc', label: 'Terlama' },
]

const productOptions = computed(() => [
  { value: '', label: 'Semua Barang' },
  ...products.value.map((product) => ({
    value: product.id,
    label: `${product.sku} — ${product.name}${product.is_active ? '' : ' (diarsipkan)'}`,
  })),
])

const hasFilters = computed(
  () =>
    Boolean(filters.search) ||
    Boolean(filters.product_id) ||
    Boolean(filters.transaction_type) ||
    Boolean(filters.date_from) ||
    Boolean(filters.date_to) ||
    filters.sort_direction !== 'desc',
)

async function loadTransactions() {
  loading.value = true
  error.value = ''
  filterError.value = ''

  try {
    const response = await transactionService.list({
      search: filters.search || undefined,
      product_id: filters.product_id || undefined,
      transaction_type: filters.transaction_type || undefined,
      date_from: filters.date_from || undefined,
      date_to: filters.date_to || undefined,
      sort_direction: filters.sort_direction,
      page: filters.page,
      per_page: 15,
    })

    transactions.value = response.data.data
    meta.value = response.data.meta
  } catch (requestError) {
    const validationErrors = getValidationErrors(requestError)

    if (validationErrors.date_to) {
      filterError.value = validationErrors.date_to
      transactions.value = []
      meta.value = { total: 0, current_page: 1, last_page: 1, from: 0, to: 0 }
    } else {
      error.value = getErrorMessage(requestError, 'Riwayat stok gagal dimuat.')
    }
  } finally {
    loading.value = false
  }
}

async function loadProducts() {
  try {
    const response = await productService.options({ include_inactive: 1, limit: 500 })
    products.value = response.data.data
  } catch {
    // Product filter is optional; history remains usable.
  }
}

function resetFilters() {
  filters.search = ''
  filters.product_id = ''
  filters.transaction_type = ''
  filters.date_from = ''
  filters.date_to = ''
  filters.sort_direction = 'desc'
  filters.page = 1
  loadTransactions()
}

function changePage(page) {
  filters.page = page
  loadTransactions()
}

function scheduleLoad() {
  clearTimeout(filterTimeout)
  filterTimeout = setTimeout(() => {
    filters.page = 1
    loadTransactions()
  }, 350)
}

watch(
  () => [
    filters.search,
    filters.product_id,
    filters.transaction_type,
    filters.date_from,
    filters.date_to,
    filters.sort_direction,
  ],
  scheduleLoad,
)

onMounted(() => {
  loadProducts()
  loadTransactions()
})

onBeforeUnmount(() => clearTimeout(filterTimeout))
</script>

<template>
  <div>
    <PageHeader title="Riwayat Stok" description="Riwayat pergerakan stok.">
      <template #actions>
        <RouterLink to="/stock-in" class="btn btn-secondary">
          <ArrowDownToLine class="h-4 w-4" aria-hidden="true" />
          Barang Masuk
        </RouterLink>
        <RouterLink to="/stock-out" class="btn btn-secondary">
          <ArrowUpFromLine class="h-4 w-4" aria-hidden="true" />
          Barang Keluar
        </RouterLink>
      </template>
    </PageHeader>

    <div class="filter-bar">
      <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
        <div class="lg:col-span-4">
          <SearchInput
            v-model="filters.search"
            placeholder="Cari referensi, kode, atau nama barang..."
            label="Cari Riwayat"
          />
        </div>
        <div class="lg:col-span-3">
          <FilterDropdown v-model="filters.product_id" :options="productOptions" label="Barang" />
        </div>
        <div class="lg:col-span-2">
          <FilterDropdown v-model="filters.transaction_type" :options="typeOptions" label="Jenis" />
        </div>
        <div class="lg:col-span-2">
          <FilterDropdown v-model="filters.sort_direction" :options="sortOptions" label="Urutan" />
        </div>
        <div class="flex items-end lg:col-span-1">
          <button
            v-if="hasFilters"
            type="button"
            class="btn btn-ghost btn-sm w-full"
            @click="resetFilters"
          >
            <RotateCcw class="h-4 w-4" aria-hidden="true" />
            Reset
          </button>
        </div>
      </div>

      <div class="mt-3">
        <DateRangePicker
          v-model:from="filters.date_from"
          v-model:to="filters.date_to"
          :max="todayInput()"
          :error="filterError"
        />
      </div>
    </div>

    <div v-if="error" class="card">
      <ErrorState :message="error" @retry="loadTransactions" />
    </div>

    <div v-else class="card overflow-hidden">
      <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div>
          <h2 class="section-title">Pergerakan Stok</h2>
          <p class="mt-0.5 text-xs text-slate-500">{{ meta.total }} transaksi ditemukan</p>
        </div>
        <button
          type="button"
          class="btn-icon-sm"
          aria-label="Muat ulang riwayat"
          :disabled="loading"
          @click="loadTransactions"
        >
          <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" aria-hidden="true" />
        </button>
      </div>

      <DataTable
        :columns="columns"
        :rows="transactions"
        :loading="loading"
      >
        <template #cell-transaction_date="{ row }">
          <span class="text-slate-600">{{ formatTransactionDateTime(row.transaction_date) }}</span>
        </template>

        <template #cell-transaction_number="{ row }">
          <span class="font-mono text-xs font-medium text-slate-600">{{ row.transaction_number }}</span>
        </template>

        <template #cell-product="{ row }">
          <div class="flex min-w-48 items-center gap-2">
            <div class="min-w-0">
              <RouterLink
                v-if="row.product"
                :to="{ name: 'products.show', params: { id: row.product.id } }"
                class="block truncate font-medium text-slate-800 hover:text-indigo-700"
              >
                {{ row.product.name }}
              </RouterLink>
              <span v-else class="text-slate-500">Barang tidak tersedia</span>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ row.product?.sku }}
                <span v-if="row.product && !row.product.is_active" class="text-amber-600">• diarsipkan</span>
              </p>
            </div>
          </div>
        </template>

        <template #cell-transaction_type="{ row }">
          <StatusBadge :status="row.transaction_type" />
        </template>

        <template #cell-quantity="{ row }">
          <span
            class="font-semibold"
            :class="row.transaction_type === 'in' ? 'text-emerald-700' : 'text-sky-700'"
          >
            {{ row.transaction_type === 'in' ? '+' : '-' }}{{ formatNumber(row.quantity) }}
            <span class="text-xs font-normal text-slate-500">{{ row.product?.unit?.abbreviation }}</span>
          </span>
        </template>

        <template #cell-stock="{ row }">
          <span class="text-sm text-slate-600">
            {{ formatNumber(row.stock_before) }}
            <span class="mx-1 text-slate-300">→</span>
            <span class="font-semibold text-slate-900">{{ formatNumber(row.stock_after) }}</span>
          </span>
        </template>

        <template #cell-unit_price="{ row }">
          {{ formatCurrency(row.unit_price) }}
        </template>

        <template #cell-description="{ row }">
          <span
            class="block max-w-52 truncate text-sm text-slate-500"
            :title="row.description || row.notes || ''"
          >
            {{ row.description || row.notes || '-' }}
          </span>
        </template>

        <template #cell-created_by="{ row }">
          <span class="text-slate-500">{{ row.created_by?.name || '-' }}</span>
        </template>

        <template #empty>
          <EmptyState
            :icon="hasFilters ? SearchX : History"
            :title="hasFilters ? 'Transaksi tidak ditemukan' : 'Belum ada transaksi'"
            :message="
              hasFilters
                ? 'Ubah kata kunci, tanggal, atau filter yang digunakan.'
                : 'Transaksi barang masuk dan keluar akan tampil di sini.'
            "
          >
            <template #action>
              <button v-if="hasFilters" type="button" class="btn btn-secondary" @click="resetFilters">
                Reset Filter
              </button>
            </template>
          </EmptyState>
        </template>
      </DataTable>

      <Pagination
        v-if="!loading && transactions.length > 0"
        :meta="meta"
        @update:page="changePage"
      />
    </div>
  </div>
</template>
