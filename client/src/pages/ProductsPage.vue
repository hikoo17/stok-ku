<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import {
  Archive,
  Eye,
  Package,
  Pencil,
  Plus,
  RefreshCw,
  RotateCcw,
  SearchX,
} from 'lucide-vue-next'
import PageHeader from '../components/PageHeader.vue'
import SearchInput from '../components/SearchInput.vue'
import FilterDropdown from '../components/FilterDropdown.vue'
import DataTable from '../components/DataTable.vue'
import Pagination from '../components/Pagination.vue'
import StatusBadge from '../components/StatusBadge.vue'
import ConfirmationDialog from '../components/ConfirmationDialog.vue'
import EmptyState from '../components/EmptyState.vue'
import ErrorState from '../components/ErrorState.vue'
import { productService } from '../services/products'
import { categoryService } from '../services/categories'
import { useToast } from '../composables/useToast'
import { getErrorMessage } from '../composables/useApiError'
import { formatCurrency, formatNumber } from '../composables/useFormat'

const toast = useToast()

const columns = [
  { key: 'sku', label: 'Kode', sortable: true },
  { key: 'name', label: 'Nama Barang', sortable: true },
  { key: 'category_name', label: 'Kategori' },
  { key: 'unit_abbreviation', label: 'Satuan', align: 'center' },
  { key: 'purchase_price', label: 'Harga Beli', sortable: true, align: 'right' },
  { key: 'selling_price', label: 'Harga Jual', sortable: true, align: 'right' },
  { key: 'current_stock', label: 'Stok', sortable: true, align: 'right' },
  { key: 'stock_status', label: 'Status' },
  { key: 'actions', label: 'Aksi', align: 'right' },
]

const filters = reactive({
  search: '',
  category_id: '',
  stock_status: '',
  status: 'all',
  sort_by: 'created_at',
  sort_direction: 'desc',
  page: 1,
  per_page: 15,
})

const products = ref([])
const meta = ref({ total: 0, current_page: 1, last_page: 1, from: 0, to: 0 })
const categories = ref([])
const loading = ref(true)
const error = ref('')
const archiveTarget = ref(null)
const archiving = ref(false)
const restoringId = ref(null)
let searchTimeout = null

const stockStatusOptions = [
  { value: '', label: 'Semua Status Stok' },
  { value: 'in_stock', label: 'Stok Aman' },
  { value: 'low_stock', label: 'Stok Menipis' },
  { value: 'out_of_stock', label: 'Stok Habis' },
]

const productStatusOptions = [
  { value: 'all', label: 'Semua Produk' },
  { value: 'active', label: 'Aktif' },
  { value: 'inactive', label: 'Diarsipkan' },
]

const categoryOptions = computed(() => [
  { value: '', label: 'Semua Kategori' },
  ...categories.value.map((category) => ({
    value: category.id,
    label: category.is_active ? category.name : `${category.name} (nonaktif)`,
  })),
])

const hasActiveFilters = computed(
  () =>
    Boolean(filters.search) ||
    Boolean(filters.category_id) ||
    Boolean(filters.stock_status) ||
    filters.status !== 'all',
)

async function loadProducts() {
  loading.value = true
  error.value = ''

  try {
    const response = await productService.list({
      search: filters.search || undefined,
      category_id: filters.category_id || undefined,
      stock_status: filters.stock_status || undefined,
      status: filters.status,
      sort_by: filters.sort_by,
      sort_direction: filters.sort_direction,
      page: filters.page,
      per_page: filters.per_page,
    })

    products.value = response.data.data
    meta.value = response.data.meta
  } catch (requestError) {
    error.value = getErrorMessage(requestError, 'Daftar barang gagal dimuat.')
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  try {
    const response = await categoryService.list({ status: 'all', per_page: 100 })
    categories.value = response.data.data
  } catch {
    // The page remains usable without the optional category filter.
  }
}

function resetFilters() {
  filters.search = ''
  filters.category_id = ''
  filters.stock_status = ''
  filters.status = 'all'
  filters.page = 1
  loadProducts()
}

function handleSort(key) {
  if (filters.sort_by === key) {
    filters.sort_direction = filters.sort_direction === 'asc' ? 'desc' : 'asc'
  } else {
    filters.sort_by = key
    filters.sort_direction = 'asc'
  }

  filters.page = 1
  loadProducts()
}

function changePage(page) {
  filters.page = page
  loadProducts()
}

async function confirmArchive() {
  if (!archiveTarget.value || archiving.value) {
    return
  }

  archiving.value = true

  try {
    await productService.archive(archiveTarget.value.id)
    toast.success('Barang berhasil diarsipkan.')
    archiveTarget.value = null

    if (products.value.length === 1 && filters.page > 1) {
      filters.page -= 1
    }

    await loadProducts()
  } catch (requestError) {
    toast.error(getErrorMessage(requestError, 'Barang gagal diarsipkan.'))
  } finally {
    archiving.value = false
  }
}

async function restoreProduct(product) {
  if (restoringId.value) {
    return
  }

  restoringId.value = product.id

  try {
    await productService.restore(product.id)
    toast.success('Barang berhasil diaktifkan kembali.')
    await loadProducts()
  } catch (requestError) {
    toast.error(getErrorMessage(requestError, 'Barang gagal diaktifkan kembali.'))
  } finally {
    restoringId.value = null
  }
}

watch(
  () => filters.search,
  () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      filters.page = 1
      loadProducts()
    }, 350)
  },
)

watch(
  () => [filters.category_id, filters.stock_status, filters.status],
  () => {
    filters.page = 1
    loadProducts()
  },
)

onMounted(() => {
  loadCategories()
  loadProducts()
})

onBeforeUnmount(() => clearTimeout(searchTimeout))
</script>

<template>
  <div>
    <PageHeader
      title="Data Barang"
      description="Kelola barang, harga, dan batas stok minimum."
    >
      <template #actions>
        <RouterLink :to="{ name: 'products.create' }" class="btn btn-primary btn-sm">
          <Plus class="h-4 w-4" aria-hidden="true" />
          Tambah Barang
        </RouterLink>
      </template>
    </PageHeader>

    <div class="mb-4 card p-4">
      <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
        <div class="min-w-0 flex-1">
          <SearchInput
            v-model="filters.search"
            placeholder="Cari kode atau nama barang..."
            label="Cari barang"
          />
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:w-[540px]">
          <FilterDropdown
            v-model="filters.category_id"
            :options="categoryOptions"
            label="Kategori"
          />
          <FilterDropdown
            v-model="filters.stock_status"
            :options="stockStatusOptions"
            label="Status Stok"
          />
          <FilterDropdown
            v-model="filters.status"
            :options="productStatusOptions"
            label="Data Produk"
          />
        </div>

        <button
          v-if="hasActiveFilters"
          type="button"
          class="btn btn-ghost btn-sm shrink-0"
          @click="resetFilters"
        >
          <RefreshCw class="h-3.5 w-3.5" aria-hidden="true" />
          Reset
        </button>
      </div>
    </div>

    <div v-if="error" class="card">
      <ErrorState :message="error" @retry="loadProducts" />
    </div>

    <div v-else class="card overflow-hidden">
      <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div>
          <h2 class="section-title">Daftar Barang</h2>
          <p class="mt-0.5 text-xs text-slate-500">
            {{ meta.total }} barang ditemukan
          </p>
        </div>
        <button
          type="button"
          class="btn-icon h-8 w-8"
          aria-label="Muat ulang data barang"
          :disabled="loading"
          @click="loadProducts"
        >
          <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" aria-hidden="true" />
        </button>
      </div>

      <DataTable
        :columns="columns"
        :rows="products"
        :loading="loading"
        :sort-by="filters.sort_by"
        :sort-direction="filters.sort_direction"
        empty-title="Belum ada barang"
        empty-message="Tambahkan barang pertama untuk mulai mencatat persediaan."
        @sort="handleSort"
      >
        <template #cell-sku="{ row }">
          <span class="font-mono text-xs font-medium text-slate-600">{{ row.sku }}</span>
        </template>

        <template #cell-name="{ row }">
          <div class="flex items-center gap-2">
            <RouterLink
              :to="{ name: 'products.show', params: { id: row.id } }"
              class="font-medium text-slate-800 hover:text-indigo-700"
            >
              {{ row.name }}
            </RouterLink>
            <StatusBadge
              v-if="!row.is_active"
              status="inactive"
              label="Diarsipkan"
              :dot="false"
              class="shrink-0"
            />
          </div>
        </template>

        <template #cell-category_name="{ row }">
          <span class="text-slate-600">{{ row.category?.name || '-' }}</span>
        </template>

        <template #cell-unit_abbreviation="{ row }">
          <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
            {{ row.unit?.abbreviation || '-' }}
          </span>
        </template>

        <template #cell-purchase_price="{ row }">
          {{ formatCurrency(row.purchase_price) }}
        </template>

        <template #cell-selling_price="{ row }">
          {{ formatCurrency(row.selling_price) }}
        </template>

        <template #cell-current_stock="{ row }">
          <span class="font-semibold text-slate-900">{{ formatNumber(row.current_stock) }}</span>
          <span class="ml-1 text-xs text-slate-500">{{ row.unit?.abbreviation }}</span>
        </template>

        <template #cell-stock_status="{ row }">
          <StatusBadge v-if="row.is_active" :status="row.stock_status" />
          <span v-else class="text-xs text-slate-500">-</span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-1">
            <RouterLink
              :to="{ name: 'products.show', params: { id: row.id } }"
              class="btn-icon h-8 w-8"
              :aria-label="`Lihat detail ${row.name}`"
              title="Lihat detail"
            >
              <Eye class="h-4 w-4" aria-hidden="true" />
            </RouterLink>
            <RouterLink
              :to="{ name: 'products.edit', params: { id: row.id } }"
              class="btn-icon h-8 w-8"
              :aria-label="`Edit ${row.name}`"
              title="Edit"
            >
              <Pencil class="h-4 w-4" aria-hidden="true" />
            </RouterLink>
            <button
              v-if="row.is_active"
              type="button"
              class="btn-icon h-8 w-8 hover:bg-amber-50 hover:text-amber-700"
              :aria-label="`Arsipkan ${row.name}`"
              title="Arsipkan"
              @click="archiveTarget = row"
            >
              <Archive class="h-4 w-4" aria-hidden="true" />
            </button>
            <button
              v-else
              type="button"
              class="btn-icon h-8 w-8 hover:bg-emerald-50 hover:text-emerald-700"
              :aria-label="`Aktifkan kembali ${row.name}`"
              title="Aktifkan kembali"
              :disabled="restoringId === row.id"
              @click="restoreProduct(row)"
            >
              <RefreshCw
                class="h-4 w-4"
                :class="restoringId === row.id ? 'animate-spin' : ''"
                aria-hidden="true"
              />
            </button>
          </div>
        </template>

        <template #empty>
          <EmptyState
            :icon="hasActiveFilters ? SearchX : Package"
            :title="hasActiveFilters ? 'Barang tidak ditemukan' : 'Belum ada barang'"
            :message="
              hasActiveFilters
                ? 'Coba ubah kata kunci atau filter yang digunakan.'
                : 'Tambahkan barang pertama untuk mulai mencatat persediaan.'
            "
          >
            <template #action>
              <button v-if="hasActiveFilters" type="button" class="btn btn-secondary btn-sm" @click="resetFilters">
                Reset Filter
              </button>
              <RouterLink v-else :to="{ name: 'products.create' }" class="btn btn-primary btn-sm">
                <Plus class="h-4 w-4" aria-hidden="true" />
                Tambah Barang
              </RouterLink>
            </template>
          </EmptyState>
        </template>
      </DataTable>

      <Pagination v-if="!loading && products.length > 0" :meta="meta" @update:page="changePage" />
    </div>

    <ConfirmationDialog
      :model-value="Boolean(archiveTarget)"
      title="Arsipkan barang?"
      :message="`Barang “${archiveTarget?.name || ''}” akan diarsipkan dan tidak dapat dipilih untuk transaksi baru. Riwayat transaksi tetap tersimpan.`"
      confirm-label="Ya, Arsipkan"
      variant="danger"
      :loading="archiving"
      @update:model-value="archiveTarget = null"
      @confirm="confirmArchive"
    />
  </div>
</template>
