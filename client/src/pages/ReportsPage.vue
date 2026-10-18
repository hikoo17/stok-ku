<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Boxes, History, TriangleAlert } from 'lucide-vue-next'
import PageHeader from '../components/PageHeader.vue'
import SearchInput from '../components/SearchInput.vue'
import FilterDropdown from '../components/FilterDropdown.vue'
import DateRangePicker from '../components/DateRangePicker.vue'
import DataTable from '../components/DataTable.vue'
import Pagination from '../components/Pagination.vue'
import StatusBadge from '../components/StatusBadge.vue'
import ErrorState from '../components/ErrorState.vue'
import Skeleton from '../components/Skeleton.vue'
import ReportExportActions from '../components/ReportExportActions.vue'
import { reportService } from '../services/reports'
import { categoryService } from '../services/categories'
import { productService } from '../services/products'
import { useToast } from '../composables/useToast'
import { getErrorMessage } from '../composables/useApiError'
import {
  formatCurrency,
  formatDate,
  formatDateTime,
  formatNumber,
  formatShortDate,
  startOfMonthInput,
  todayInput,
} from '../composables/useFormat'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const TABS = [
  { id: 'inventory', label: 'Stok Saat Ini', icon: Boxes },
  { id: 'movements', label: 'Pergerakan Stok', icon: History },
  { id: 'low-stock', label: 'Stok Menipis', icon: TriangleAlert },
]

const initialTab = TABS.some((tab) => tab.id === route.query.tab) ? route.query.tab : 'inventory'
const activeTab = ref(initialTab)
const loaded = reactive({ inventory: false, movements: false, 'low-stock': false })

const categories = ref([])
const products = ref([])

const categoryOptions = computed(() => [
  { value: '', label: 'Semua Kategori' },
  ...categories.value.map((category) => ({
    value: category.id,
    label: category.is_active ? category.name : `${category.name} (nonaktif)`,
  })),
])

const productOptions = computed(() => [
  { value: '', label: 'Semua Barang' },
  ...products.value.map((product) => ({
    value: product.id,
    label: `${product.sku} — ${product.name}`,
  })),
])

const stockStatusOptions = [
  { value: '', label: 'Semua Status Stok' },
  { value: 'in_stock', label: 'Stok Aman' },
  { value: 'low_stock', label: 'Stok Menipis' },
  { value: 'out_of_stock', label: 'Stok Habis' },
]

const transactionTypeOptions = [
  { value: '', label: 'Semua Jenis' },
  { value: 'in', label: 'Barang Masuk' },
  { value: 'out', label: 'Barang Keluar' },
]

function createReportState() {
  return {
    rows: [],
    meta: { total: 0, current_page: 1, last_page: 1, from: 0, to: 0 },
    summary: null,
    generatedAt: '',
    loading: false,
    loaded: false,
    error: '',
    exportingFormat: '',
  }
}

const inventory = reactive({
  ...createReportState(),
  filters: { search: '', category_id: '', stock_status: '', page: 1 },
})

const movements = reactive({
  ...createReportState(),
  filters: {
    search: '',
    category_id: '',
    product_id: '',
    transaction_type: '',
    date_from: startOfMonthInput(),
    date_to: todayInput(),
    page: 1,
  },
})

const lowStock = reactive({
  ...createReportState(),
  filters: { search: '', category_id: '', page: 1 },
})

const inventoryColumns = [
  { key: 'sku', label: 'Kode' },
  { key: 'name', label: 'Nama Barang' },
  { key: 'category_name', label: 'Kategori' },
  { key: 'unit_abbreviation', label: 'Satuan', align: 'center' },
  { key: 'current_stock', label: 'Stok', align: 'right' },
  { key: 'minimum_stock', label: 'Minimum', align: 'right' },
  { key: 'purchase_price', label: 'Harga Beli', align: 'right' },
  { key: 'selling_price', label: 'Harga Jual', align: 'right' },
  { key: 'stock_status', label: 'Status' },
]

const movementColumns = [
  { key: 'transaction_date', label: 'Tanggal' },
  { key: 'transaction_number', label: 'Referensi' },
  { key: 'product', label: 'Barang' },
  { key: 'transaction_type', label: 'Jenis' },
  { key: 'quantity', label: 'Jumlah', align: 'right' },
  { key: 'stock_before', label: 'Stok Sebelum', align: 'right' },
  { key: 'stock_after', label: 'Stok Sesudah', align: 'right' },
  { key: 'unit_price', label: 'Harga', align: 'right' },
  { key: 'description', label: 'Keterangan' },
]

const lowStockColumns = [
  { key: 'sku', label: 'Kode' },
  { key: 'name', label: 'Nama Barang' },
  { key: 'category_name', label: 'Kategori' },
  { key: 'unit_abbreviation', label: 'Satuan', align: 'center' },
  { key: 'current_stock', label: 'Stok', align: 'right' },
  { key: 'minimum_stock', label: 'Minimum', align: 'right' },
  { key: 'shortage', label: 'Kekurangan', align: 'right' },
  { key: 'stock_status', label: 'Status' },
  { key: 'purchase_price', label: 'Harga Beli', align: 'right' },
]

let inventoryTimer = null
let movementsTimer = null
let lowStockTimer = null

async function loadOptions() {
  try {
    const [categoryResponse, productResponse] = await Promise.all([
      categoryService.list({ status: 'all', per_page: 100 }),
      productService.options({ include_inactive: 1, limit: 500 }),
    ])

    categories.value = categoryResponse.data.data
    products.value = productResponse.data.data
  } catch {
    // Filters degrade gracefully when option data cannot be loaded.
  }
}

async function loadInventory() {
  inventory.loading = true
  inventory.error = ''

  try {
    const response = await reportService.inventory({
      search: inventory.filters.search || undefined,
      category_id: inventory.filters.category_id || undefined,
      stock_status: inventory.filters.stock_status || undefined,
      page: inventory.filters.page,
      per_page: 15,
    })

    inventory.rows = response.data.data
    inventory.meta = response.data.meta
    inventory.generatedAt = response.data.generated_at
    inventory.loaded = true
    loaded.inventory = true
  } catch (requestError) {
    inventory.error = getErrorMessage(requestError, 'Laporan stok gagal dimuat.')
  } finally {
    inventory.loading = false
  }
}

async function loadMovements() {
  movements.loading = true
  movements.error = ''

  try {
    const response = await reportService.stockMovements({
      search: movements.filters.search || undefined,
      category_id: movements.filters.category_id || undefined,
      product_id: movements.filters.product_id || undefined,
      transaction_type: movements.filters.transaction_type || undefined,
      date_from: movements.filters.date_from || undefined,
      date_to: movements.filters.date_to || undefined,
      page: movements.filters.page,
      per_page: 15,
    })

    movements.rows = response.data.data
    movements.meta = response.data.meta
    movements.summary = response.data.summary
    movements.generatedAt = response.data.generated_at
    movements.loaded = true
    loaded.movements = true
  } catch (requestError) {
    movements.error = getErrorMessage(requestError, 'Laporan pergerakan stok gagal dimuat.')
  } finally {
    movements.loading = false
  }
}

async function loadLowStock() {
  lowStock.loading = true
  lowStock.error = ''

  try {
    const response = await reportService.lowStock({
      search: lowStock.filters.search || undefined,
      category_id: lowStock.filters.category_id || undefined,
      page: lowStock.filters.page,
      per_page: 15,
    })

    lowStock.rows = response.data.data
    lowStock.meta = response.data.meta
    lowStock.summary = response.data.summary
    lowStock.generatedAt = response.data.generated_at
    lowStock.loaded = true
    loaded['low-stock'] = true
  } catch (requestError) {
    lowStock.error = getErrorMessage(requestError, 'Laporan stok menipis gagal dimuat.')
  } finally {
    lowStock.loading = false
  }
}

function loadTab(tab) {
  if (tab === 'inventory') {
    return loadInventory()
  }

  if (tab === 'movements') {
    return loadMovements()
  }

  return loadLowStock()
}

function setTab(tab) {
  if (activeTab.value === tab) {
    return
  }

  activeTab.value = tab
  router.replace({ query: { ...route.query, tab } })

  if (!loaded[tab]) {
    loadTab(tab)
  }
}

function inventoryParams() {
  return {
    search: inventory.filters.search || undefined,
    category_id: inventory.filters.category_id || undefined,
    stock_status: inventory.filters.stock_status || undefined,
  }
}

function movementParams() {
  return {
    search: movements.filters.search || undefined,
    category_id: movements.filters.category_id || undefined,
    product_id: movements.filters.product_id || undefined,
    transaction_type: movements.filters.transaction_type || undefined,
    date_from: movements.filters.date_from || undefined,
    date_to: movements.filters.date_to || undefined,
  }
}

function lowStockParams() {
  return {
    search: lowStock.filters.search || undefined,
    category_id: lowStock.filters.category_id || undefined,
  }
}

async function downloadReport(type, format, state, params) {
  if (state.exportingFormat) {
    return
  }

  state.exportingFormat = format

  try {
    await reportService.download(type, params, format)
    toast.success(`Laporan ${format.toUpperCase()} berhasil diunduh.`)
  } catch (requestError) {
    toast.error(getErrorMessage(requestError, 'Laporan gagal diunduh. Silakan coba lagi.'))
  } finally {
    state.exportingFormat = ''
  }
}

function clearTimer(key) {
  if (key === 'inventory') {
    clearTimeout(inventoryTimer)
  }

  if (key === 'movements') {
    clearTimeout(movementsTimer)
  }

  if (key === 'low-stock') {
    clearTimeout(lowStockTimer)
  }
}

function scheduleLoad(key) {
  clearTimer(key)

  const state = { inventory, movements, 'low-stock': lowStock }[key]
  const loader = { inventory: loadInventory, movements: loadMovements, 'low-stock': loadLowStock }[key]

  const timer = setTimeout(() => {
    state.filters.page = 1
    loader()
  }, 350)

  if (key === 'inventory') inventoryTimer = timer
  if (key === 'movements') movementsTimer = timer
  if (key === 'low-stock') lowStockTimer = timer
}

function resetInventoryFilters() {
  inventory.filters.search = ''
  inventory.filters.category_id = ''
  inventory.filters.stock_status = ''
  inventory.filters.page = 1
  loadInventory()
}

function resetMovementFilters() {
  movements.filters.search = ''
  movements.filters.category_id = ''
  movements.filters.product_id = ''
  movements.filters.transaction_type = ''
  movements.filters.date_from = startOfMonthInput()
  movements.filters.date_to = todayInput()
  movements.filters.page = 1
  loadMovements()
}

function resetLowStockFilters() {
  lowStock.filters.search = ''
  lowStock.filters.category_id = ''
  lowStock.filters.page = 1
  loadLowStock()
}

function changeInventoryPage(page) {
  inventory.filters.page = page
  loadInventory()
}

function changeMovementPage(page) {
  movements.filters.page = page
  loadMovements()
}

function changeLowStockPage(page) {
  lowStock.filters.page = page
  loadLowStock()
}

onMounted(() => {
  loadOptions()
  loadTab(activeTab.value)
})

onBeforeUnmount(() => {
  clearTimeout(inventoryTimer)
  clearTimeout(movementsTimer)
  clearTimeout(lowStockTimer)
})
</script>

<template>
  <div>
    <PageHeader title="Laporan" description="Laporan persediaan barang." />

    <div class="mb-5 overflow-x-auto">
      <div class="inline-flex min-w-full gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm sm:min-w-0" role="tablist" aria-label="Jenis laporan">
        <button
          v-for="tab in TABS"
          :key="tab.id"
          type="button"
          role="tab"
          :aria-selected="activeTab === tab.id"
          class="flex h-10 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-lg px-4 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 sm:flex-none"
          :class="
            activeTab === tab.id
              ? 'bg-indigo-600 text-white shadow-sm'
              : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          "
          @click="setTab(tab.id)"
        >
          <Boxes v-if="tab.id === 'inventory'" class="h-4 w-4" aria-hidden="true" />
          <History v-else-if="tab.id === 'movements'" class="h-4 w-4" aria-hidden="true" />
          <TriangleAlert v-else class="h-4 w-4" aria-hidden="true" />
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Current inventory report -->
    <div v-show="activeTab === 'inventory'" role="tabpanel">
      <div class="filter-bar">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
          <div class="min-w-0 flex-1">
            <SearchInput
              :model-value="inventory.filters.search"
              placeholder="Cari kode atau nama barang..."
              label="Cari Barang"
              @update:model-value="
                (value) => {
                  inventory.filters.search = value
                  scheduleLoad('inventory')
                }
              "
            />
          </div>
          <div class="sm:w-56">
            <FilterDropdown
              :model-value="inventory.filters.category_id"
              :options="categoryOptions"
              label="Kategori"
              @update:model-value="
                (value) => {
                  inventory.filters.category_id = value
                  scheduleLoad('inventory')
                }
              "
            />
          </div>
          <div class="sm:w-48">
            <FilterDropdown
              :model-value="inventory.filters.stock_status"
              :options="stockStatusOptions"
              label="Status Stok"
              @update:model-value="
                (value) => {
                  inventory.filters.stock_status = value
                  scheduleLoad('inventory')
                }
              "
            />
          </div>
          <button type="button" class="btn btn-ghost btn-sm" @click="resetInventoryFilters">
            Reset
          </button>
        </div>
      </div>

      <div class="card overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 class="section-title">Laporan Stok Saat Ini</h2>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ inventory.meta.total }} barang
                <span v-if="inventory.generatedAt"> &middot; diperbarui {{ formatDateTime(inventory.generatedAt) }}</span>
              </p>
            </div>
            <ReportExportActions
              :loading="inventory.loading"
              :exporting="inventory.exportingFormat"
              @refresh="loadInventory"
              @download="(format) => downloadReport('inventory', format, inventory, inventoryParams())"
            />
          </div>
        </div>

        <ErrorState v-if="inventory.error" :message="inventory.error" @retry="loadInventory" />

        <template v-else>
          <DataTable
            :columns="inventoryColumns"
            :rows="inventory.rows"
            :loading="inventory.loading"
            empty-title="Tidak ada data stok"
            empty-message="Belum ada barang aktif yang sesuai filter."
          >
            <template #cell-sku="{ row }">
              <span class="font-mono text-xs font-medium text-slate-600">{{ row.sku }}</span>
            </template>
            <template #cell-name="{ row }">
              <span class="font-medium text-slate-800">{{ row.name }}</span>
            </template>
            <template #cell-category_name="{ row }">{{ row.category?.name || '-' }}</template>
            <template #cell-unit_abbreviation="{ row }">
              <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                {{ row.unit?.abbreviation || '-' }}
              </span>
            </template>
            <template #cell-current_stock="{ row }">
              <span class="font-semibold text-slate-900">{{ formatNumber(row.current_stock) }}</span>
            </template>
            <template #cell-minimum_stock="{ row }">{{ formatNumber(row.minimum_stock) }}</template>
            <template #cell-purchase_price="{ row }">{{ formatCurrency(row.purchase_price) }}</template>
            <template #cell-selling_price="{ row }">{{ formatCurrency(row.selling_price) }}</template>
            <template #cell-stock_status="{ row }">
              <StatusBadge :status="row.stock_status" />
            </template>
          </DataTable>

          <Pagination
            v-if="!inventory.loading && inventory.rows.length > 0"
            :meta="inventory.meta"
            @update:page="changeInventoryPage"
          />
        </template>
      </div>
    </div>

    <!-- Stock movement report -->
    <div v-show="activeTab === 'movements'" role="tabpanel">
      <div class="filter-bar">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
          <div class="lg:col-span-4">
            <SearchInput
              :model-value="movements.filters.search"
              placeholder="Cari referensi atau barang..."
              label="Cari Pergerakan"
              @update:model-value="
                (value) => {
                  movements.filters.search = value
                  scheduleLoad('movements')
                }
              "
            />
          </div>
          <div class="lg:col-span-3">
            <FilterDropdown
              :model-value="movements.filters.product_id"
              :options="productOptions"
              label="Barang"
              @update:model-value="
                (value) => {
                  movements.filters.product_id = value
                  scheduleLoad('movements')
                }
              "
            />
          </div>
          <div class="lg:col-span-3">
            <FilterDropdown
              :model-value="movements.filters.category_id"
              :options="categoryOptions"
              label="Kategori"
              @update:model-value="
                (value) => {
                  movements.filters.category_id = value
                  scheduleLoad('movements')
                }
              "
            />
          </div>
          <div class="lg:col-span-2">
            <FilterDropdown
              :model-value="movements.filters.transaction_type"
              :options="transactionTypeOptions"
              label="Jenis"
              @update:model-value="
                (value) => {
                  movements.filters.transaction_type = value
                  scheduleLoad('movements')
                }
              "
            />
          </div>
        </div>

        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
          <div class="flex-1">
            <DateRangePicker
              :from="movements.filters.date_from"
              :to="movements.filters.date_to"
              :max="todayInput()"
              @update:from="
                (value) => {
                  movements.filters.date_from = value
                  scheduleLoad('movements')
                }
              "
              @update:to="
                (value) => {
                  movements.filters.date_to = value
                  scheduleLoad('movements')
                }
              "
            />
          </div>
          <button type="button" class="btn btn-ghost btn-sm" @click="resetMovementFilters">
            Reset Periode
          </button>
        </div>
      </div>

      <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <template v-if="movements.loading && !movements.loaded">
          <div v-for="index in 3" :key="index" class="card p-5" aria-hidden="true">
            <Skeleton width="45%" height="0.75rem" />
            <Skeleton width="30%" height="1.5rem" class="mt-2" />
            <Skeleton width="60%" height="0.65rem" class="mt-2" />
          </div>
        </template>
        <template v-else>
          <div class="card p-5">
            <p class="text-sm font-medium text-slate-500">Total Barang Masuk</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700">
              {{ formatNumber(movements.summary?.total_in || 0) }}
            </p>
            <p class="mt-1 text-xs text-slate-500">Dalam periode terpilih</p>
          </div>
          <div class="card p-5">
            <p class="text-sm font-medium text-slate-500">Total Barang Keluar</p>
            <p class="mt-1 text-2xl font-semibold text-sky-700">
              {{ formatNumber(movements.summary?.total_out || 0) }}
            </p>
            <p class="mt-1 text-xs text-slate-500">Dalam periode terpilih</p>
          </div>
          <div class="card p-5">
            <p class="text-sm font-medium text-slate-500">Jumlah Transaksi</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">
              {{ formatNumber(movements.summary?.total_transactions || 0) }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
              <template v-if="movements.summary">
                {{ formatDate(movements.summary.date_from) }} – {{ formatDate(movements.summary.date_to) }}
              </template>
              <template v-else>Periode terpilih</template>
            </p>
          </div>
        </template>
      </div>

      <div class="card overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="section-title">Laporan Pergerakan Stok</h2>
            <p class="mt-0.5 text-xs text-slate-500">
              {{ movements.meta.total }} transaksi
              <span v-if="movements.generatedAt"> &middot; diperbarui {{ formatDateTime(movements.generatedAt) }}</span>
            </p>
          </div>
          <ReportExportActions
            :loading="movements.loading"
            :exporting="movements.exportingFormat"
            @refresh="loadMovements"
            @download="(format) => downloadReport('movements', format, movements, movementParams())"
          />
        </div>

        <ErrorState v-if="movements.error" :message="movements.error" @retry="loadMovements" />

        <template v-else>
          <DataTable
            :columns="movementColumns"
            :rows="movements.rows"
            :loading="movements.loading"
            empty-title="Tidak ada pergerakan stok"
            empty-message="Tidak ada transaksi pada periode dan filter yang dipilih."
          >
            <template #cell-transaction_date="{ row }">
              <span class="text-slate-600">{{ formatShortDate(row.transaction_date) }}</span>
            </template>
            <template #cell-transaction_number="{ row }">
              <span class="font-mono text-xs font-medium text-slate-600">{{ row.transaction_number }}</span>
            </template>
            <template #cell-product="{ row }">
              <div class="min-w-40">
                <p class="font-medium text-slate-800">{{ row.product?.name || '-' }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ row.product?.sku }}</p>
              </div>
            </template>
            <template #cell-transaction_type="{ row }">
              <StatusBadge :status="row.transaction_type" />
            </template>
            <template #cell-quantity="{ row }">
              <span class="font-semibold" :class="row.transaction_type === 'in' ? 'text-emerald-700' : 'text-sky-700'">
                {{ row.transaction_type === 'in' ? '+' : '-' }}{{ formatNumber(row.quantity) }}
                <span class="text-xs font-normal text-slate-500">{{ row.product?.unit?.abbreviation }}</span>
              </span>
            </template>
            <template #cell-stock_before="{ row }">
              <span class="text-sm text-slate-600">{{ formatNumber(row.stock_before) }}</span>
            </template>
            <template #cell-stock_after="{ row }">
              <span class="text-sm font-semibold text-slate-900">{{ formatNumber(row.stock_after) }}</span>
            </template>
            <template #cell-unit_price="{ row }">{{ formatCurrency(row.unit_price) }}</template>
            <template #cell-description="{ row }">
              <span class="block max-w-48 truncate text-slate-500" :title="row.description || row.notes || ''">
                {{ row.description || row.notes || '-' }}
              </span>
            </template>
          </DataTable>

          <Pagination
            v-if="!movements.loading && movements.rows.length > 0"
            :meta="movements.meta"
            @update:page="changeMovementPage"
          />
        </template>
      </div>
    </div>

    <!-- Low stock report -->
    <div v-show="activeTab === 'low-stock'" role="tabpanel">
      <div class="filter-bar">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
          <div class="min-w-0 flex-1">
            <SearchInput
              :model-value="lowStock.filters.search"
              placeholder="Cari kode atau nama barang..."
              label="Cari Barang"
              @update:model-value="
                (value) => {
                  lowStock.filters.search = value
                  scheduleLoad('low-stock')
                }
              "
            />
          </div>
          <div class="sm:w-60">
            <FilterDropdown
              :model-value="lowStock.filters.category_id"
              :options="categoryOptions"
              label="Kategori"
              @update:model-value="
                (value) => {
                  lowStock.filters.category_id = value
                  scheduleLoad('low-stock')
                }
              "
            />
          </div>
          <button type="button" class="btn btn-ghost btn-sm" @click="resetLowStockFilters">
            Reset
          </button>
        </div>
      </div>

      <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <template v-if="lowStock.loading && !lowStock.loaded">
          <div v-for="index in 3" :key="index" class="card p-5" aria-hidden="true">
            <Skeleton width="45%" height="0.75rem" />
            <Skeleton width="30%" height="1.5rem" class="mt-2" />
            <Skeleton width="60%" height="0.65rem" class="mt-2" />
          </div>
        </template>
        <template v-else>
          <div class="card p-5">
            <p class="text-sm font-medium text-slate-500">Perlu Perhatian</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ formatNumber(lowStock.summary?.total || 0) }}</p>
            <p class="mt-1 text-xs text-slate-500">Pada atau di bawah batas minimum</p>
          </div>
          <div class="card p-5">
            <p class="text-sm font-medium text-slate-500">Stok Menipis</p>
            <p class="mt-1 text-2xl font-semibold text-amber-700">{{ formatNumber(lowStock.summary?.low_stock || 0) }}</p>
            <p class="mt-1 text-xs text-slate-500">Masih tersedia namun menipis</p>
          </div>
          <div class="card p-5">
            <p class="text-sm font-medium text-slate-500">Stok Habis</p>
            <p class="mt-1 text-2xl font-semibold text-red-700">{{ formatNumber(lowStock.summary?.out_of_stock || 0) }}</p>
            <p class="mt-1 text-xs text-slate-500">Stok nol</p>
          </div>
        </template>
      </div>

      <div class="card overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="section-title">Laporan Stok Menipis</h2>
            <p class="mt-0.5 text-xs text-slate-500">
              {{ lowStock.meta.total }} barang
              <span v-if="lowStock.generatedAt"> &middot; diperbarui {{ formatDateTime(lowStock.generatedAt) }}</span>
            </p>
          </div>
          <ReportExportActions
            :loading="lowStock.loading"
            :exporting="lowStock.exportingFormat"
            @refresh="loadLowStock"
            @download="(format) => downloadReport('low-stock', format, lowStock, lowStockParams())"
          />
        </div>

        <ErrorState v-if="lowStock.error" :message="lowStock.error" @retry="loadLowStock" />

        <template v-else>
          <DataTable
            :columns="lowStockColumns"
            :rows="lowStock.rows"
            :loading="lowStock.loading"
            empty-title="Tidak ada stok menipis"
            empty-message="Semua barang berada di atas batas minimum."
          >
            <template #cell-sku="{ row }">
              <span class="font-mono text-xs font-medium text-slate-600">{{ row.sku }}</span>
            </template>
            <template #cell-name="{ row }">
              <span class="font-medium text-slate-800">{{ row.name }}</span>
            </template>
            <template #cell-category_name="{ row }">{{ row.category?.name || '-' }}</template>
            <template #cell-unit_abbreviation="{ row }">
              <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                {{ row.unit?.abbreviation || '-' }}
              </span>
            </template>
            <template #cell-current_stock="{ row }">
              <span class="font-semibold" :class="row.current_stock <= 0 ? 'text-red-700' : 'text-amber-700'">
                {{ formatNumber(row.current_stock) }}
              </span>
            </template>
            <template #cell-minimum_stock="{ row }">{{ formatNumber(row.minimum_stock) }}</template>
            <template #cell-shortage="{ row }">
              <span class="font-medium text-slate-700">
                {{ formatNumber(Math.max(0, row.minimum_stock - row.current_stock)) }}
              </span>
            </template>
            <template #cell-stock_status="{ row }">
              <StatusBadge :status="row.stock_status" />
            </template>
            <template #cell-purchase_price="{ row }">{{ formatCurrency(row.purchase_price) }}</template>
          </DataTable>

          <Pagination
            v-if="!lowStock.loading && lowStock.rows.length > 0"
            :meta="lowStock.meta"
            @update:page="changeLowStockPage"
          />
        </template>
      </div>
    </div>
  </div>
</template>
