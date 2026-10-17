<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  AlertTriangle,
  ArrowDownToLine,
  ArrowUpFromLine,
  Boxes,
  Package,
  PackageMinus,
  PackagePlus,
  Plus,
  XCircle,
} from 'lucide-vue-next'
import {
  BarElement,
  CategoryScale,
  Chart as ChartJS,
  Legend,
  LinearScale,
  Tooltip,
} from 'chart.js'
import { Bar } from 'vue-chartjs'
import PageHeader from '../components/PageHeader.vue'
import StatCard from '../components/StatCard.vue'
import StatusBadge from '../components/StatusBadge.vue'
import ErrorState from '../components/ErrorState.vue'
import EmptyState from '../components/EmptyState.vue'
import Skeleton from '../components/Skeleton.vue'
import SkeletonStatCard from '../components/SkeletonStatCard.vue'
import { dashboardService } from '../services/dashboard'
import { formatDate, formatNumber, formatShortDate } from '../composables/useFormat'
import { getErrorMessage } from '../composables/useApiError'

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend)

const loading = ref(true)
const error = ref('')
const dashboard = ref(null)
const chartSkeletonHeights = ['44%', '72%', '38%', '88%', '60%', '46%', '80%']

const summary = computed(
  () =>
    dashboard.value?.summary || {
      total_products: 0,
      total_stock: 0,
      low_stock: 0,
      out_of_stock: 0,
    },
)

const chartData = computed(() => ({
  labels: dashboard.value?.chart?.map((item) => item.label) || [],
  datasets: [
    {
      label: 'Barang Masuk',
      data: dashboard.value?.chart?.map((item) => item.stock_in) || [],
      backgroundColor: '#10b981',
      borderRadius: 5,
      maxBarThickness: 26,
    },
    {
      label: 'Barang Keluar',
      data: dashboard.value?.chart?.map((item) => item.stock_out) || [],
      backgroundColor: '#6366f1',
      borderRadius: 5,
      maxBarThickness: 26,
    },
  ],
}))

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  animation: { duration: 300 },
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        usePointStyle: true,
        pointStyle: 'circle',
        boxWidth: 8,
        boxHeight: 8,
        padding: 18,
        color: '#64748b',
        font: { family: 'Inter', size: 12 },
      },
    },
    tooltip: {
      backgroundColor: '#0f172a',
      padding: 10,
      titleFont: { family: 'Inter', size: 12 },
      bodyFont: { family: 'Inter', size: 12 },
      callbacks: {
        label(context) {
          return `${context.dataset.label}: ${formatNumber(context.parsed.y)}`
        },
      },
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: '#94a3b8', font: { family: 'Inter', size: 11 } },
      border: { display: false },
    },
    y: {
      beginAtZero: true,
      grid: { color: '#f1f5f9' },
      ticks: {
        color: '#94a3b8',
        font: { family: 'Inter', size: 11 },
        callback(value) {
          return formatNumber(value)
        },
      },
      border: { display: false },
    },
  },
}))

async function loadDashboard() {
  loading.value = true
  error.value = ''

  try {
    const response = await dashboardService.get()
    dashboard.value = response.data.data
  } catch (requestError) {
    error.value = getErrorMessage(requestError, 'Ringkasan dashboard gagal dimuat.')
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <div>
    <PageHeader
      title="Dashboard"
      :description="`Ringkasan persediaan per ${formatDate(new Date())}.`"
    >
      <template #actions>
        <RouterLink to="/products/create" class="btn btn-primary">
          <Plus class="h-4 w-4" aria-hidden="true" />
          Tambah Barang
        </RouterLink>
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

    <div v-if="loading" class="space-y-6" role="status" aria-label="Memuat dashboard">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <SkeletonStatCard v-for="index in 4" :key="index" />
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
          <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <Skeleton width="9rem" height="1rem" />
            <Skeleton width="13rem" height="0.7rem" class="mt-2" />
          </div>
          <div class="flex h-72 items-end gap-3 px-6 py-6 sm:h-80">
            <Skeleton
              v-for="(height, index) in chartSkeletonHeights"
              :key="index"
              width="100%"
              :height="height"
              class="flex-1 rounded-t-md"
            />
          </div>
        </div>

        <div class="card">
          <div class="border-b border-slate-100 px-5 py-4">
            <Skeleton width="8rem" height="1rem" />
            <Skeleton width="6rem" height="0.7rem" class="mt-2" />
          </div>
          <div class="space-y-5 p-5">
            <div v-for="index in 4" :key="index" class="flex items-start justify-between gap-4">
              <div class="flex-1 space-y-2">
                <Skeleton width="80%" height="0.85rem" />
                <Skeleton width="40%" height="0.7rem" />
              </div>
              <Skeleton width="4.5rem" height="1.25rem" class="rounded-full" />
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div v-for="index in 2" :key="index" class="card">
          <div class="border-b border-slate-100 px-5 py-4">
            <Skeleton width="10rem" height="1rem" />
          </div>
          <div class="divide-y divide-slate-100">
            <div v-for="row in 5" :key="row" class="flex items-center gap-3 px-5 py-3.5">
              <Skeleton width="2.25rem" height="2.25rem" circle />
              <div class="flex-1 space-y-2">
                <Skeleton width="65%" height="0.8rem" />
                <Skeleton width="45%" height="0.65rem" />
              </div>
              <Skeleton width="3.5rem" height="0.8rem" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="error" class="card">
      <ErrorState :message="error" @retry="loadDashboard" />
    </div>

    <div v-else class="space-y-6">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          label="Total Barang Aktif"
          :value="formatNumber(summary.total_products)"
          :icon="Package"
          tone="indigo"
          hint="Dapat ditransaksikan"
          to="/products"
        />
        <StatCard
          label="Total Stok"
          :value="formatNumber(summary.total_stock)"
          :icon="Boxes"
          tone="slate"
          hint="Tanpa konversi satuan"
        />
        <StatCard
          label="Stok Menipis"
          :value="formatNumber(summary.low_stock)"
          :icon="AlertTriangle"
          tone="amber"
          hint="Pada atau di bawah batas minimum"
          to="/reports?tab=low-stock"
        />
        <StatCard
          label="Stok Habis"
          :value="formatNumber(summary.out_of_stock)"
          :icon="XCircle"
          tone="red"
          hint="Tidak memiliki stok"
          to="/reports?tab=low-stock"
        />
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
          <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
              <h2 class="section-title">Pergerakan Stok</h2>
              <p class="mt-0.5 text-xs text-slate-500">Barang masuk dan keluar 7 hari terakhir.</p>
            </div>
            <RouterLink to="/reports?tab=movements" class="link text-xs">Lihat laporan</RouterLink>
          </div>
          <div class="h-72 px-2 py-4 sm:h-80 sm:px-4">
            <Bar :data="chartData" :options="chartOptions" />
          </div>
        </div>

        <div class="card flex flex-col">
          <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
              <h2 class="section-title">Stok Menipis</h2>
              <p class="mt-0.5 text-xs text-slate-500">Perlu segera ditambah.</p>
            </div>
            <RouterLink to="/reports?tab=low-stock" class="link text-xs">Semua</RouterLink>
          </div>

          <div v-if="dashboard.low_stock_products.length === 0" class="flex flex-1 items-center">
            <EmptyState
              :icon="Package"
              title="Semua stok aman"
              message="Tidak ada barang yang menyentuh batas minimum."
            />
          </div>

          <ul v-else class="divide-y divide-slate-100">
            <li
              v-for="product in dashboard.low_stock_products"
              :key="product.id"
              class="px-5 py-3.5"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <RouterLink
                    :to="`/products/${product.id}`"
                    class="block truncate text-sm font-medium text-slate-800 hover:text-indigo-700"
                  >
                    {{ product.name }}
                  </RouterLink>
                  <p class="mt-0.5 text-xs text-slate-500">{{ product.sku }}</p>
                </div>
                <StatusBadge :status="product.stock_status" />
              </div>
              <p class="mt-2 text-xs text-slate-500">
                Stok
                <span class="font-semibold text-slate-700">{{ formatNumber(product.current_stock) }}</span>
                {{ product.unit?.abbreviation }} &middot; minimum
                {{ formatNumber(product.minimum_stock) }}
              </p>
            </li>
          </ul>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="card">
          <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-2.5">
              <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                <ArrowDownToLine class="h-4 w-4" aria-hidden="true" />
              </span>
              <div>
                <h2 class="section-title">Barang Masuk Terbaru</h2>
                <p class="text-xs text-slate-500">5 transaksi terakhir</p>
              </div>
            </div>
            <RouterLink to="/history?transaction_type=in" class="link text-xs">Semua</RouterLink>
          </div>

          <EmptyState
            v-if="dashboard.recent_stock_in.length === 0"
            :icon="PackagePlus"
            title="Belum ada barang masuk"
            message="Transaksi barang masuk akan tampil di sini."
          />

          <ul v-else class="divide-y divide-slate-100">
            <li
              v-for="transaction in dashboard.recent_stock_in"
              :key="transaction.id"
              class="flex items-center gap-3 px-5 py-3.5 sm:px-6"
            >
              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <ArrowDownToLine class="h-4 w-4" aria-hidden="true" />
              </span>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-800">
                  {{ transaction.product?.name || 'Barang dihapus' }}
                </p>
                <p class="mt-0.5 truncate text-xs text-slate-500">
                  {{ transaction.transaction_number }} &middot;
                  {{ formatShortDate(transaction.transaction_date) }}
                </p>
              </div>
              <span class="shrink-0 text-sm font-semibold text-emerald-700">
                +{{ formatNumber(transaction.quantity) }}
                <span class="text-xs font-normal text-slate-500">{{ transaction.product?.unit?.abbreviation }}</span>
              </span>
            </li>
          </ul>
        </div>

        <div class="card">
          <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-2.5">
              <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                <ArrowUpFromLine class="h-4 w-4" aria-hidden="true" />
              </span>
              <div>
                <h2 class="section-title">Barang Keluar Terbaru</h2>
                <p class="text-xs text-slate-500">5 transaksi terakhir</p>
              </div>
            </div>
            <RouterLink to="/history?transaction_type=out" class="link text-xs">Semua</RouterLink>
          </div>

          <EmptyState
            v-if="dashboard.recent_stock_out.length === 0"
            :icon="PackageMinus"
            title="Belum ada barang keluar"
            message="Transaksi barang keluar akan tampil di sini."
          />

          <ul v-else class="divide-y divide-slate-100">
            <li
              v-for="transaction in dashboard.recent_stock_out"
              :key="transaction.id"
              class="flex items-center gap-3 px-5 py-3.5 sm:px-6"
            >
              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sky-50 text-sky-600">
                <ArrowUpFromLine class="h-4 w-4" aria-hidden="true" />
              </span>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-800">
                  {{ transaction.product?.name || 'Barang dihapus' }}
                </p>
                <p class="mt-0.5 truncate text-xs text-slate-500">
                  {{ transaction.transaction_number }} &middot;
                  {{ formatShortDate(transaction.transaction_date) }}
                </p>
              </div>
              <span class="shrink-0 text-sm font-semibold text-sky-700">
                -{{ formatNumber(transaction.quantity) }}
                <span class="text-xs font-normal text-slate-500">{{ transaction.product?.unit?.abbreviation }}</span>
              </span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>
