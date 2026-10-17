import { createRouter, createWebHistory } from 'vue-router'
import AppLayout from '../layouts/AppLayout.vue'
import { useAuth } from '../composables/useAuth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/LoginPage.vue'),
    meta: { title: 'Masuk', public: true },
  },
  {
    path: '/',
    component: AppLayout,
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('../pages/DashboardPage.vue'),
        meta: { title: 'Dashboard' },
      },
      {
        path: 'products',
        name: 'products.index',
        component: () => import('../pages/ProductsPage.vue'),
        meta: { title: 'Data Barang' },
      },
      {
        path: 'products/create',
        name: 'products.create',
        component: () => import('../pages/ProductFormPage.vue'),
        meta: { title: 'Tambah Barang' },
      },
      {
        path: 'products/:id',
        name: 'products.show',
        component: () => import('../pages/ProductDetailPage.vue'),
        meta: { title: 'Detail Barang' },
      },
      {
        path: 'products/:id/edit',
        name: 'products.edit',
        component: () => import('../pages/ProductFormPage.vue'),
        meta: { title: 'Edit Barang' },
      },
      {
        path: 'categories',
        name: 'categories.index',
        component: () => import('../pages/CategoriesPage.vue'),
        meta: { title: 'Kategori' },
      },
      {
        path: 'units',
        name: 'units.index',
        component: () => import('../pages/UnitsPage.vue'),
        meta: { title: 'Satuan' },
      },
      {
        path: 'stock-in',
        name: 'stock-in',
        component: () => import('../pages/StockInPage.vue'),
        meta: { title: 'Barang Masuk' },
      },
      {
        path: 'stock-out',
        name: 'stock-out',
        component: () => import('../pages/StockOutPage.vue'),
        meta: { title: 'Barang Keluar' },
      },
      {
        path: 'history',
        name: 'history',
        component: () => import('../pages/HistoryPage.vue'),
        meta: { title: 'Riwayat Stok' },
      },
      {
        path: 'reports',
        name: 'reports',
        component: () => import('../pages/ReportsPage.vue'),
        meta: { title: 'Laporan' },
      },
      {
        path: ':pathMatch(.*)*',
        name: 'not-found',
        component: () => import('../pages/NotFoundPage.vue'),
        meta: { title: 'Halaman Tidak Ditemukan' },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach(async (to) => {
  const auth = useAuth()
  await auth.ensureInitialized()

  if (!to.meta.public && !auth.state.token) {
    return {
      name: 'login',
      query: { redirect: to.fullPath },
    }
  }

  if (to.name === 'login' && auth.state.token) {
    return { name: 'dashboard' }
  }

  document.title = `${to.meta.title || 'Stok Ku'} — Stok Ku`

  return true
})

export default router
