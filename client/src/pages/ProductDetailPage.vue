<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  Archive,
  ArrowDownToLine,
  ArrowLeft,
  ArrowUpFromLine,
  Boxes,
  CalendarDays,
  History,
  Pencil,
  RefreshCw,
  Tag,
  Wallet,
} from 'lucide-vue-next'
import PageHeader from '../components/PageHeader.vue'
import StatusBadge from '../components/StatusBadge.vue'
import Skeleton from '../components/Skeleton.vue'
import ErrorState from '../components/ErrorState.vue'
import EmptyState from '../components/EmptyState.vue'
import ConfirmationDialog from '../components/ConfirmationDialog.vue'
import { productService } from '../services/products'
import { transactionService } from '../services/transactions'
import { useToast } from '../composables/useToast'
import { getErrorMessage } from '../composables/useApiError'
import { formatCurrency, formatDateTime, formatNumber, formatTransactionDateTime } from '../composables/useFormat'

const route = useRoute()
const toast = useToast()

const product = ref(null)
const transactions = ref([])
const loading = ref(true)
const error = ref('')
const archiveOpen = ref(false)
const archiveLoading = ref(false)
const restoring = ref(false)

const minimumHint = computed(() => {
  if (!product.value) {
    return ''
  }

  return `Batas minimum ${formatNumber(product.value.minimum_stock)} ${product.value.unit?.abbreviation || ''}`
})

async function loadProduct() {
  loading.value = true
  error.value = ''

  try {
    const [productResponse, transactionResponse] = await Promise.all([
      productService.get(route.params.id),
      transactionService.list({ product_id: route.params.id, per_page: 5 }),
    ])

    product.value = productResponse.data.data
    transactions.value = transactionResponse.data.data
  } catch (requestError) {
    error.value = getErrorMessage(requestError, 'Detail barang gagal dimuat.')
  } finally {
    loading.value = false
  }
}

async function archiveProduct() {
  if (archiveLoading.value) {
    return
  }

  archiveLoading.value = true

  try {
    await productService.archive(product.value.id)
    toast.success('Barang berhasil diarsipkan.')
    archiveOpen.value = false
    await loadProduct()
  } catch (requestError) {
    toast.error(getErrorMessage(requestError, 'Barang gagal diarsipkan.'))
  } finally {
    archiveLoading.value = false
  }
}

async function restoreProduct() {
  if (restoring.value) {
    return
  }

  restoring.value = true

  try {
    await productService.restore(product.value.id)
    toast.success('Barang berhasil diaktifkan kembali.')
    await loadProduct()
  } catch (requestError) {
    toast.error(getErrorMessage(requestError, 'Barang gagal diaktifkan kembali.'))
  } finally {
    restoring.value = false
  }
}

function stockInLink() {
  return { name: 'stock-in', query: { product_id: route.params.id } }
}

function stockOutLink() {
  return { name: 'stock-out', query: { product_id: route.params.id } }
}

onMounted(loadProduct)
</script>

<template>
  <div>
    <PageHeader
      title="Detail Barang"
      description="Informasi dan riwayat stok barang."
    >
      <template #actions>
        <RouterLink to="/products" class="btn btn-secondary">
          <ArrowLeft class="h-4 w-4" aria-hidden="true" />
          Kembali
        </RouterLink>
        <template v-if="product">
          <RouterLink
            :to="{ name: 'products.edit', params: { id: product.id } }"
            class="btn btn-secondary"
          >
            <Pencil class="h-4 w-4" aria-hidden="true" />
            Edit
          </RouterLink>
          <button
            v-if="product.is_active"
            type="button"
            class="btn btn-secondary text-amber-700 hover:bg-amber-50"
            @click="archiveOpen = true"
          >
            <Archive class="h-4 w-4" aria-hidden="true" />
            Arsipkan
          </button>
          <button
            v-else
            type="button"
            class="btn btn-success"
            :disabled="restoring"
            @click="restoreProduct"
          >
            <RefreshCw class="h-4 w-4" :class="restoring ? 'animate-spin' : ''" aria-hidden="true" />
            {{ restoring ? 'Mengaktifkan...' : 'Aktifkan Kembali' }}
          </button>
        </template>
      </template>
    </PageHeader>

    <div v-if="loading" class="space-y-6" role="status" aria-label="Memuat detail barang">
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
          <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div class="flex-1 space-y-2.5">
              <Skeleton width="6rem" height="1.1rem" />
              <Skeleton width="60%" height="1.4rem" />
              <Skeleton width="40%" height="0.8rem" />
            </div>
            <div class="space-y-2 sm:text-right">
              <Skeleton width="5rem" height="1.2rem" class="rounded-full sm:ml-auto" />
              <Skeleton width="7rem" height="1.75rem" class="sm:ml-auto" />
              <Skeleton width="6rem" height="0.65rem" class="sm:ml-auto" />
            </div>
          </div>
          <div class="grid grid-cols-1 gap-6 p-5 sm:grid-cols-2 sm:p-6">
            <div v-for="column in 2" :key="column" class="space-y-5">
              <div v-for="row in 2" :key="row" class="flex items-start gap-3">
                <Skeleton width="2.25rem" height="2.25rem" class="rounded-lg" />
                <div class="flex-1 space-y-2">
                  <Skeleton width="50%" height="0.65rem" />
                  <Skeleton width="70%" height="0.9rem" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="card p-5 sm:p-6">
            <Skeleton width="8rem" height="1rem" />
            <Skeleton width="90%" height="0.7rem" class="mt-2" />
            <div class="mt-4 space-y-2">
              <Skeleton height="2.75rem" class="rounded-lg" />
              <Skeleton height="2.75rem" class="rounded-lg" />
            </div>
          </div>
          <div class="card p-5 sm:p-6">
            <Skeleton width="6rem" height="1rem" />
            <Skeleton width="100%" height="0.7rem" class="mt-3" />
            <Skeleton width="80%" height="0.7rem" class="mt-2" />
          </div>
        </div>
      </div>

      <div class="card">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
          <Skeleton width="10rem" height="1rem" />
        </div>
        <div class="divide-y divide-slate-100">
          <div v-for="row in 5" :key="row" class="grid grid-cols-4 gap-4 px-5 py-3.5">
            <Skeleton height="0.8rem" />
            <Skeleton height="0.8rem" />
            <Skeleton height="0.8rem" />
            <Skeleton height="0.8rem" />
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="error" class="card">
      <ErrorState :message="error" @retry="loadProduct" />
    </div>

    <div v-else-if="product" class="space-y-6">
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="card lg:col-span-2">
          <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-medium text-slate-600">
                  {{ product.sku }}
                </span>
                <StatusBadge :status="product.is_active ? 'active' : 'inactive'" :label="product.is_active ? 'Aktif' : 'Diarsipkan'" />
              </div>
              <h2 class="mt-2 text-xl font-semibold text-slate-900">{{ product.name }}</h2>
              <p class="mt-1 text-sm text-slate-500">
                {{ product.category?.name || '-' }} &middot; Satuan {{ product.unit?.name }} ({{ product.unit?.abbreviation }})
              </p>
            </div>
            <div class="shrink-0 text-left sm:text-right">
              <StatusBadge :status="product.stock_status" />
              <p class="mt-2 text-2xl font-semibold text-slate-900">
                {{ formatNumber(product.current_stock) }}
                <span class="text-sm font-normal text-slate-500">{{ product.unit?.abbreviation }}</span>
              </p>
              <p class="text-xs text-slate-500">{{ minimumHint }}</p>
            </div>
          </div>

          <dl class="grid grid-cols-1 divide-y divide-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
            <div class="space-y-5 p-5 sm:p-6">
              <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                  <Wallet class="h-4 w-4" aria-hidden="true" />
                </span>
                <div>
                  <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Harga Beli</dt>
                  <dd class="mt-1 text-base font-semibold text-slate-900">{{ formatCurrency(product.purchase_price) }}</dd>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                  <Wallet class="h-4 w-4" aria-hidden="true" />
                </span>
                <div>
                  <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Harga Jual</dt>
                  <dd class="mt-1 text-base font-semibold text-slate-900">{{ formatCurrency(product.selling_price) }}</dd>
                </div>
              </div>
            </div>

            <div class="space-y-5 p-5 sm:p-6">
              <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                  <Tag class="h-4 w-4" aria-hidden="true" />
                </span>
                <div>
                  <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Kategori</dt>
                  <dd class="mt-1 text-sm font-medium text-slate-900">{{ product.category?.name || '-' }}</dd>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                  <Boxes class="h-4 w-4" aria-hidden="true" />
                </span>
                <div>
                  <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Satuan</dt>
                  <dd class="mt-1 text-sm font-medium text-slate-900">
                    {{ product.unit?.name }} ({{ product.unit?.abbreviation }})
                  </dd>
                </div>
              </div>
            </div>
          </dl>

          <div v-if="product.description" class="border-t border-slate-100 p-5 sm:p-6">
            <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500">Deskripsi</h3>
            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ product.description }}</p>
          </div>
        </section>

        <div class="space-y-6">
          <section class="card p-5 sm:p-6">
            <h2 class="section-title">Transaksi Cepat</h2>
            <div class="mt-4 space-y-2">
              <RouterLink
                :to="stockInLink()"
                class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50/50 px-4 py-3 text-sm font-medium text-emerald-800 transition hover:bg-emerald-50"
              >
                <span class="flex items-center gap-2.5">
                  <ArrowDownToLine class="h-4 w-4" aria-hidden="true" />
                  Barang Masuk
                </span>
                <span class="text-xs text-emerald-600">Tambah stok</span>
              </RouterLink>
              <RouterLink
                :to="stockOutLink()"
                class="flex items-center justify-between rounded-lg border border-sky-200 bg-sky-50/50 px-4 py-3 text-sm font-medium text-sky-800 transition hover:bg-sky-50"
              >
                <span class="flex items-center gap-2.5">
                  <ArrowUpFromLine class="h-4 w-4" aria-hidden="true" />
                  Barang Keluar
                </span>
                <span class="text-xs text-sky-600">Kurangi stok</span>
              </RouterLink>
            </div>
          </section>

          <section class="card border-dashed p-5 sm:p-6">
            <div class="flex items-start gap-3">
              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                <CalendarDays class="h-4 w-4" aria-hidden="true" />
              </span>
              <div>
                <h2 class="text-sm font-semibold text-slate-900">Informasi</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">
                  Dibuat {{ formatDateTime(product.created_at) }}.
                  Terakhir diperbarui {{ formatDateTime(product.updated_at) }}.
                </p>
              </div>
            </div>
          </section>
        </div>
      </div>

      <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
          <div class="flex items-center gap-2.5">
            <History class="h-5 w-5 text-slate-500" aria-hidden="true" />
            <div>
              <h2 class="section-title">Riwayat Stok Terbaru</h2>
              <p class="text-xs text-slate-500">5 transaksi terakhir untuk barang ini.</p>
            </div>
          </div>
          <RouterLink :to="{ name: 'history', query: { product_id: product.id } }" class="link text-xs">
            Lihat semua
          </RouterLink>
        </div>

        <EmptyState
          v-if="transactions.length === 0"
          :icon="History"
          title="Belum ada transaksi"
          message="Riwayat barang masuk dan keluar akan tampil di sini."
        />

        <div v-else class="overflow-x-auto">
          <table class="table-base">
            <thead class="table-head">
              <tr>
                <th class="table-th">Tanggal</th>
                <th class="table-th">Referensi</th>
                <th class="table-th">Jenis</th>
                <th class="table-th text-right">Jumlah</th>
                <th class="table-th text-right">Stok Sesudah</th>
                <th class="table-th text-right">Harga</th>
                <th class="table-th">Keterangan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="transaction in transactions" :key="transaction.id" class="table-row">
                <td class="table-td">{{ formatTransactionDateTime(transaction.transaction_date) }}</td>
                <td class="table-td font-mono text-xs">{{ transaction.transaction_number }}</td>
                <td class="table-td">
                  <StatusBadge :status="transaction.transaction_type" />
                </td>
                <td class="table-td text-right font-medium">
                  {{ transaction.transaction_type === 'in' ? '+' : '-' }}{{ formatNumber(transaction.quantity) }}
                  {{ product.unit?.abbreviation }}
                </td>
                <td class="table-td text-right">{{ formatNumber(transaction.stock_after) }}</td>
                <td class="table-td text-right">{{ formatCurrency(transaction.unit_price) }}</td>
                <td class="table-td max-w-xs truncate text-slate-500">
                  {{ transaction.description || transaction.notes || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <ConfirmationDialog
      :model-value="archiveOpen"
      title="Arsipkan barang?"
      :message="`Barang “${product?.name || ''}” akan diarsipkan. Riwayat transaksi tetap tersimpan.`"
      confirm-label="Ya, Arsipkan"
      variant="danger"
      :loading="archiveLoading"
      @update:model-value="archiveOpen = false"
      @confirm="archiveProduct"
    />
  </div>
</template>
