<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { Pencil, Plus, Power, RefreshCw, Tags, Trash2 } from 'lucide-vue-next'
import PageHeader from '../components/PageHeader.vue'
import SearchInput from '../components/SearchInput.vue'
import FilterDropdown from '../components/FilterDropdown.vue'
import DataTable from '../components/DataTable.vue'
import Pagination from '../components/Pagination.vue'
import StatusBadge from '../components/StatusBadge.vue'
import AppModal from '../components/AppModal.vue'
import ConfirmationDialog from '../components/ConfirmationDialog.vue'
import FormField from '../components/FormField.vue'
import ErrorState from '../components/ErrorState.vue'
import EmptyState from '../components/EmptyState.vue'
import { categoryService } from '../services/categories'
import { useToast } from '../composables/useToast'
import { getErrorMessage, getValidationErrors } from '../composables/useApiError'
import { formatDate } from '../composables/useFormat'

const toast = useToast()

const columns = [
  { key: 'name', label: 'Nama Kategori' },
  { key: 'description', label: 'Deskripsi' },
  { key: 'products_count', label: 'Jumlah Produk', align: 'center' },
  { key: 'is_active', label: 'Status' },
  { key: 'created_at', label: 'Dibuat' },
  { key: 'actions', label: 'Aksi', align: 'right' },
]

const filters = reactive({ search: '', status: 'all', page: 1 })
const categories = ref([])
const meta = ref({ total: 0, current_page: 1, last_page: 1, from: 0, to: 0 })
const loading = ref(true)
const error = ref('')
let searchTimeout = null

const statusOptions = [
  { value: 'all', label: 'Semua Status' },
  { value: 'active', label: 'Aktif' },
  { value: 'inactive', label: 'Nonaktif' },
]

const modalOpen = ref(false)
const editingCategory = ref(null)
const form = reactive({ name: '', description: '', is_active: true })
const formErrors = reactive({})
const formError = ref('')
const saving = ref(false)

const deleteTarget = ref(null)
const deleting = ref(false)
const togglingId = ref(null)

const hasFilters = computed(() => Boolean(filters.search) || filters.status !== 'all')

async function loadCategories() {
  loading.value = true
  error.value = ''

  try {
    const response = await categoryService.list({
      search: filters.search || undefined,
      status: filters.status,
      page: filters.page,
      per_page: 15,
    })

    categories.value = response.data.data
    meta.value = response.data.meta
  } catch (requestError) {
    error.value = getErrorMessage(requestError, 'Daftar kategori gagal dimuat.')
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.search = ''
  filters.status = 'all'
  filters.page = 1
  loadCategories()
}

function changePage(page) {
  filters.page = page
  loadCategories()
}

function openCreate() {
  editingCategory.value = null
  Object.assign(form, { name: '', description: '', is_active: true })
  Object.keys(formErrors).forEach((key) => delete formErrors[key])
  formError.value = ''
  modalOpen.value = true
}

function openEdit(category) {
  editingCategory.value = category
  Object.assign(form, {
    name: category.name,
    description: category.description || '',
    is_active: category.is_active,
  })
  Object.keys(formErrors).forEach((key) => delete formErrors[key])
  formError.value = ''
  modalOpen.value = true
}

async function saveCategory() {
  if (saving.value) {
    return
  }

  Object.keys(formErrors).forEach((key) => delete formErrors[key])
  formError.value = ''

  if (!form.name.trim()) {
    formErrors.name = 'Nama kategori wajib diisi.'
    return
  }

  saving.value = true

  const payload = {
    name: form.name.trim(),
    description: form.description.trim() || null,
    is_active: form.is_active,
  }

  try {
    const response = editingCategory.value
      ? await categoryService.update(editingCategory.value.id, payload)
      : await categoryService.create(payload)

    toast.success(response.data.message || 'Kategori berhasil disimpan.')
    modalOpen.value = false
    await loadCategories()
  } catch (requestError) {
    Object.assign(formErrors, getValidationErrors(requestError))
    formError.value = getErrorMessage(requestError, 'Kategori gagal disimpan.')
  } finally {
    saving.value = false
  }
}

async function toggleActive(category) {
  if (togglingId.value) {
    return
  }

  togglingId.value = category.id

  try {
    const response = await categoryService.update(category.id, {
      name: category.name,
      description: category.description || null,
      is_active: !category.is_active,
    })
    toast.success(response.data.message || 'Status kategori berhasil diperbarui.')
    await loadCategories()
  } catch (requestError) {
    toast.error(getErrorMessage(requestError, 'Status kategori gagal diperbarui.'))
  } finally {
    togglingId.value = null
  }
}

async function deleteCategory() {
  if (!deleteTarget.value || deleting.value) {
    return
  }

  deleting.value = true

  try {
    const response = await categoryService.remove(deleteTarget.value.id)
    toast.success(response.data.message || 'Kategori berhasil dihapus.')
    deleteTarget.value = null

    if (categories.value.length === 1 && filters.page > 1) {
      filters.page -= 1
    }

    await loadCategories()
  } catch (requestError) {
    toast.error(getErrorMessage(requestError, 'Kategori gagal dihapus.'))
    deleteTarget.value = null
  } finally {
    deleting.value = false
  }
}

watch(
  () => filters.search,
  () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      filters.page = 1
      loadCategories()
    }, 350)
  },
)

watch(
  () => filters.status,
  () => {
    filters.page = 1
    loadCategories()
  },
)

onMounted(loadCategories)
onBeforeUnmount(() => clearTimeout(searchTimeout))
</script>

<template>
  <div>
    <PageHeader title="Kategori" description="Kelola kategori barang yang digunakan pada data persediaan.">
      <template #actions>
        <button type="button" class="btn btn-primary btn-sm" @click="openCreate">
          <Plus class="h-4 w-4" aria-hidden="true" />
          Tambah Kategori
        </button>
      </template>
    </PageHeader>

    <div class="mb-4 card p-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="min-w-0 flex-1">
          <SearchInput v-model="filters.search" placeholder="Cari nama atau deskripsi kategori..." label="Cari kategori" />
        </div>
        <div class="sm:w-52">
          <FilterDropdown v-model="filters.status" :options="statusOptions" label="Status" />
        </div>
        <button v-if="hasFilters" type="button" class="btn btn-ghost btn-sm" @click="resetFilters">
          <RefreshCw class="h-3.5 w-3.5" aria-hidden="true" />
          Reset
        </button>
      </div>
    </div>

    <div v-if="error" class="card">
      <ErrorState :message="error" @retry="loadCategories" />
    </div>

    <div v-else class="card overflow-hidden">
      <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div>
          <h2 class="section-title">Daftar Kategori</h2>
          <p class="mt-0.5 text-xs text-slate-500">{{ meta.total }} kategori</p>
        </div>
        <button type="button" class="btn-icon h-8 w-8" aria-label="Muat ulang kategori" :disabled="loading" @click="loadCategories">
          <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" aria-hidden="true" />
        </button>
      </div>

      <DataTable
        :columns="columns"
        :rows="categories"
        :loading="loading"
        empty-title="Belum ada kategori"
        empty-message="Tambahkan kategori untuk mengelompokkan barang."
      >
        <template #cell-name="{ row }">
          <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
              <Tags class="h-4 w-4" aria-hidden="true" />
            </span>
            <span class="font-medium text-slate-800">{{ row.name }}</span>
          </div>
        </template>

        <template #cell-description="{ row }">
          <span class="block max-w-xs truncate text-sm text-slate-500" :title="row.description || ''">
            {{ row.description || '-' }}
          </span>
        </template>

        <template #cell-products_count="{ row }">
          <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
            {{ row.products_count }}
          </span>
        </template>

        <template #cell-is_active="{ row }">
          <StatusBadge :status="row.is_active ? 'active' : 'inactive'" />
        </template>

        <template #cell-created_at="{ row }">
          <span class="text-slate-500">{{ formatDate(row.created_at) }}</span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-1">
            <button
              type="button"
              class="btn-icon h-8 w-8"
              :aria-label="`Edit kategori ${row.name}`"
              title="Edit"
              @click="openEdit(row)"
            >
              <Pencil class="h-4 w-4" aria-hidden="true" />
            </button>
            <button
              type="button"
              class="btn-icon h-8 w-8"
              :class="row.is_active ? 'hover:bg-amber-50 hover:text-amber-700' : 'hover:bg-emerald-50 hover:text-emerald-700'"
              :aria-label="row.is_active ? `Nonaktifkan ${row.name}` : `Aktifkan ${row.name}`"
              :title="row.is_active ? 'Nonaktifkan' : 'Aktifkan'"
              :disabled="togglingId === row.id"
              @click="toggleActive(row)"
            >
              <Power class="h-4 w-4" :class="togglingId === row.id ? 'animate-pulse' : ''" aria-hidden="true" />
            </button>
            <button
              type="button"
              class="btn-icon h-8 w-8 hover:bg-red-50 hover:text-red-700"
              :aria-label="`Hapus kategori ${row.name}`"
              :title="row.products_count > 0 ? 'Tidak dapat dihapus karena masih digunakan produk' : 'Hapus'"
              :disabled="row.products_count > 0"
              @click="deleteTarget = row"
            >
              <Trash2 class="h-4 w-4" aria-hidden="true" />
            </button>
          </div>
        </template>

        <template #empty>
          <EmptyState
            :icon="Tags"
            :title="hasFilters ? 'Kategori tidak ditemukan' : 'Belum ada kategori'"
            :message="hasFilters ? 'Coba ubah kata kunci atau status filter.' : 'Tambahkan kategori pertama untuk mengelompokkan barang.'"
          >
            <template #action>
              <button v-if="hasFilters" type="button" class="btn btn-secondary btn-sm" @click="resetFilters">
                Reset Filter
              </button>
              <button v-else type="button" class="btn btn-primary btn-sm" @click="openCreate">
                <Plus class="h-4 w-4" aria-hidden="true" />
                Tambah Kategori
              </button>
            </template>
          </EmptyState>
        </template>
      </DataTable>

      <Pagination v-if="!loading && categories.length > 0" :meta="meta" @update:page="changePage" />
    </div>

    <AppModal
      v-model="modalOpen"
      :title="editingCategory ? 'Edit Kategori' : 'Tambah Kategori'"
      description="Nama kategori harus unik dan dapat diubah kapan saja."
      size="md"
      :closable="!saving"
    >
      <div
        v-if="formError"
        class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        role="alert"
      >
        {{ formError }}
      </div>

      <form id="category-form" class="space-y-4" novalidate @submit.prevent="saveCategory">
        <FormField label="Nama Kategori" for-id="category-name" required :error="formErrors.name">
          <input
            id="category-name"
            v-model="form.name"
            type="text"
            maxlength="100"
            class="field-input"
            :class="{ 'field-input-error': formErrors.name }"
            placeholder="Contoh: Radiator Hose"
          />
        </FormField>

        <FormField label="Deskripsi" for-id="category-description" :error="formErrors.description" hint="Opsional.">
          <textarea
            id="category-description"
            v-model="form.description"
            rows="3"
            maxlength="1000"
            class="field-input resize-y"
            :class="{ 'field-input-error': formErrors.description }"
            placeholder="Keterangan singkat kategori..."
          />
        </FormField>

        <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-4">
          <div>
            <p class="text-sm font-medium text-slate-800">Kategori aktif</p>
            <p class="mt-0.5 text-xs text-slate-500">
              Kategori nonaktif tidak dapat dipilih untuk barang baru.
            </p>
          </div>
          <button
            type="button"
            role="switch"
            :aria-checked="form.is_active"
            aria-label="Ubah status kategori"
            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
            :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-300'"
            @click="form.is_active = !form.is_active"
          >
            <span
              class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
              :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
            />
          </button>
        </div>
      </form>

      <template #footer>
        <button type="button" class="btn btn-secondary" :disabled="saving" @click="modalOpen = false">Batal</button>
        <button type="submit" form="category-form" class="btn btn-primary" :disabled="saving">
          <span v-if="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true" />
          {{ saving ? 'Menyimpan...' : editingCategory ? 'Simpan Perubahan' : 'Tambah Kategori' }}
        </button>
      </template>
    </AppModal>

    <ConfirmationDialog
      :model-value="Boolean(deleteTarget)"
      title="Hapus kategori?"
      :message="`Kategori “${deleteTarget?.name || ''}” akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`"
      confirm-label="Ya, Hapus"
      variant="danger"
      :loading="deleting"
      @update:model-value="deleteTarget = null"
      @confirm="deleteCategory"
    />
  </div>
</template>
