<script setup>
import { computed, onMounted, ref } from 'vue'
import { AlertTriangle, ArrowDownToLine, ArrowUpFromLine, Boxes, Package, XCircle } from 'lucide-vue-next'
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
import ErrorState from '../components/ErrorState.vue'
import Skeleton from '../components/Skeleton.vue'
import SkeletonStatCard from '../components/SkeletonStatCard.vue'
import { dashboardService } from '../services/dashboard'
import { formatDate, formatNumber } from '../composables/useFormat'
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

const chartLabels = computed(() => dashboard.value?.chart?.map((item) => item.label) || [])

const incomingChart = computed(() => ({
  labels: chartLabels.value,
  datasets: [
    {
      label: 'Barang Masuk',
      data: dashboard.value?.chart?.map((item) => item.stock_in) || [],
      backgroundColor: '#10b981',
      borderRadius: 5,
      maxBarThickness: 28,
    },
  ],
}))

const outgoingChart = computed(() => ({
  labels: chartLabels.value,
  datasets: [
    {
      label: 'Barang Keluar',
      data: dashboard.value?.chart?.map((item) => item.stock_out) || [],
      backgroundColor: '#6366f1',
      borderRadius: 5,
      maxBarThickness: 28,
    },
  ],
}))

const totalIncoming = computed(() =>
  (dashboard.value?.chart || []).reduce((total, item) => total + (Number(item.stock_in) || 0), 0),
)

const totalOutgoing = computed(() =>
  (dashboard.value?.chart || []).reduce((total, item) => total + (Number(item.stock_out) || 0), 0),
)

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  animation: { duration: 300 },
  plugins: {
    legend: { display: false },
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
      ticks: { color: '#64748b', font: { family: 'Inter', size: 11 } },
      border: { display: false },
    },
    y: {
      beginAtZero: true,
      grid: { color: '#f1f5f9' },
      ticks: {
        color: '#64748b',
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
    />

    <div v-if="loading" class="space-y-6" role="status" aria-label="Memuat dashboard">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <SkeletonStatCard v-for="index in 4" :key="index" />
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div v-for="index in 2" :key="index" class="card">
          <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <Skeleton width="9rem" height="1rem" />
            <Skeleton width="7rem" height="0.7rem" class="mt-2" />
          </div>
          <div class="flex h-64 items-end gap-3 px-6 py-6 sm:h-72">
            <Skeleton
              v-for="(height, barIndex) in chartSkeletonHeights"
              :key="barIndex"
              width="100%"
              :height="height"
              class="flex-1 rounded-t-md"
            />
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
          to="/products"
        />
        <StatCard
          label="Total Stok"
          :value="formatNumber(summary.total_stock)"
          :icon="Boxes"
          tone="slate"
        />
        <StatCard
          label="Stok Menipis"
          :value="formatNumber(summary.low_stock)"
          :icon="AlertTriangle"
          tone="amber"
          to="/reports?tab=low-stock"
        />
        <StatCard
          label="Stok Habis"
          :value="formatNumber(summary.out_of_stock)"
          :icon="XCircle"
          tone="red"
          to="/reports?tab=low-stock"
        />
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="card">
          <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-2.5">
              <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                <ArrowDownToLine class="h-4 w-4" aria-hidden="true" />
              </span>
              <div>
                <h2 class="section-title">Barang Masuk</h2>
                <p class="text-xs text-slate-500">7 hari terakhir</p>
              </div>
            </div>
            <p class="text-right">
              <span class="block text-lg font-semibold text-emerald-700">{{ formatNumber(totalIncoming) }}</span>
              <span class="text-xs text-slate-500">Total</span>
            </p>
          </div>
          <div class="h-64 px-2 py-4 sm:h-72 sm:px-4">
            <Bar :data="incomingChart" :options="chartOptions" />
          </div>
        </div>

        <div class="card">
          <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-2.5">
              <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                <ArrowUpFromLine class="h-4 w-4" aria-hidden="true" />
              </span>
              <div>
                <h2 class="section-title">Barang Keluar</h2>
                <p class="text-xs text-slate-500">7 hari terakhir</p>
              </div>
            </div>
            <p class="text-right">
              <span class="block text-lg font-semibold text-indigo-700">{{ formatNumber(totalOutgoing) }}</span>
              <span class="text-xs text-slate-500">Total</span>
            </p>
          </div>
          <div class="h-64 px-2 py-4 sm:h-72 sm:px-4">
            <Bar :data="outgoingChart" :options="chartOptions" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
