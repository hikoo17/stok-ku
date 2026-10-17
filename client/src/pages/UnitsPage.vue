<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { Pencil, Plus, Power, RefreshCw, Ruler, Trash2 } from 'lucide-vue-next'
import PageHeader from '../components/PageHeader.vue'
import SearchInput from '../components/SearchInput.vue'
import FilterDropdown from '../components/FilterDropdown.vue'
import DataTable from '../components/DataTable.vue'
import Pagination from '../components/Pagination.vue'
import StatusBadge from '../components/StatusBadge.vue'
import AppModal from '../components/AppModal.vue'
import ConfirmationDialog from '../components/ConfirmationDialog.vue'
import FormField from '../components/FormField.vue'
import ToggleSwitch from '../components/ToggleSwitch.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import ErrorState from '../components/ErrorState.vue'
import EmptyState from '../components/EmptyState.vue'
import { unitService } from '../services/units'
import { useToast } from '../composables/useToast'
import { getErrorMessage, getValidationErrors } from '../composables/useApiError'
import { formatDate } from '../composables/useFormat'

const toast = useToast()

const columns = [
  { key: 'name', label: 'Nama Satuan' },
  { key: 'abbreviation', label: 'Singkatan', align: 'center' },
  { key: 'products_count', label: 'Jumlah Barang', align: 'center' },
  { key: 'is_active', label: 'Status' },
  { key: 'created_at', label: 'Dibuat' },
  { key: 'actions', label: 'Aksi', align: 'right' },
]

const filters = reactive({ search: '', status: 'all', page: 1 })
const units = ref([])
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
const editingUnit = ref(null)
const form = reactive({ name: '', abbreviation: '', is_active: true })
const formErrors = reactive({})
const formError = ref('')
const saving = ref(false)

const deleteTarget = ref(null)
const deleting = ref(false)
const togglingId = ref(null)

const hasFilters = computed(() => Boolean(filters.search) || filters.status !== 'all')

async function loadUnits() {
  loading.value = true
  error.value = ''

  try {
    const response = await unitService.list({
      search: filters.search || undefined,
      status: filters.status,
      page: filters.page,
      per_page: 15,
    })

    units.value = response.data.data
    meta.value = response.data.meta
  } catch (requestError) {
    error.value = getErrorMessage(requestError, 'Daftar satuan gagal dimuat.')
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.search = ''
  filters.status = 'all'
  filters.page = 1
  loadUnits()
}

function changePage(page) {
  filters.page = page
  loadUnits()
}

function openCreate() {
  editingUnit.value = null
  Object.assign(form, { name: '', abbreviation: '', is_active: true })
  Object.keys(formErrors).forEach((key) => delete formErrors[key])
  formError.value = ''
  modalOpen.value = true
}

function openEdit(unit) {
  editingUnit.value = unit
  Object.assign(form, {
    name: unit.name,
    abbreviation: unit.abbreviation,
    is_active: unit.is_active,
  })
  Object.keys(formErrors).forEach((key) => delete formErrors[key])
  formError.value = ''
  modalOpen.value = true
}

async function saveUnit() {
  if (saving.value) {
    return
  }

  Object.keys(formErrors).forEach((key) => delete formErrors[key])
  formError.value = ''

  if (!form.name.trim()) {
    formErrors.name = 'Nama satuan wajib diisi.'
  }

  if (!form.abbreviation.trim()) {
    formErrors.abbreviation = 'Singkatan wajib diisi.'
  }

  if (Object.keys(formErrors).length > 0) {
    return
  }

  saving.value = true

  const payload = {
    name: form.name.trim(),
    abbreviation: form.abbreviation.trim(),
    is_active: form.is_active,
  }

  try {
    const response = editingUnit.value
      ? await unitService.update(editingUnit.value.id, payload)
      : await unitService.create(payload)

    toast.success(response.data.message || 'Satuan berhasil disimpan.')
    modalOpen.value = false
    await loadUnits()
  } catch (requestError) {
    Object.assign(formErrors, getValidationErrors(requestError))
    formError.value = getErrorMessage(requestError, 'Satuan gagal disimpan.')
  } finally {
    saving.value = false
  }
}

async function toggleActive(unit) {
  if (togglingId.value) {
    return
  }

  togglingId.value = unit.id

  try {
    const response = await unitService.update(unit.id, {
      name: unit.name,
      abbreviation: unit.abbreviation,
      is_active: !unit.is_active,
    })
    toast.success(response.data.message || 'Status satuan berhasil diperbarui.')
    await loadUnits()
  } catch (requestError) {
    toast.error(getErrorMessage(requestError, 'Status satuan gagal diperbarui.'))
  } finally {
    togglingId.value = null
  }
}

async function deleteUnit() {
  if (!deleteTarget.value || deleting.value) {
    return
  }

  deleting.value = true

  try {
    const response = await unitService.remove(deleteTarget.value.id)
    toast.success(response.data.message || 'Satuan berhasil dihapus.')
    deleteTarget.value = null

    if (units.value.length === 1 && filters.page > 1) {
      filters.page -= 1
    }

    await loadUnits()
  } catch (requestError) {
    toast.error(getErrorMessage(requestError, 'Satuan gagal dihapus.'))
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
      loadUnits()
    }, 350)
  },
)

watch(
  () => filters.status,
  () => {
    filters.page = 1
    loadUnits()
  },
)

onMounted(loadUnits)
onBeforeUnmount(() => clearTimeout(searchTimeout))
</script>

<template>
  <div>
    <PageHeader title="Satuan" description="Kelola satuan barang.">
      <template #actions>
        <button type="button" class="btn btn-primary" @click="openCreate">
          <Plus class="h-4 w-4" aria-hidden="true" />
          Tambah Satuan
        </button>
      </template>
    </PageHeader>

    <div class="filter-bar">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="min-w-0 flex-1">
          <SearchInput
            v-model="filters.search"
            placeholder="Cari nama atau singkatan satuan..."
            label="Cari Satuan"
          />
        </div>
        <div class="sm:w-52">
          <FilterDropdown v-model="filters.status" :options="statusOptions" label="Status" />
        </div>
        <button v-if="hasFilters" type="button" class="btn btn-ghost btn-sm" @click="resetFilters">
          <RefreshCw class="h-4 w-4" aria-hidden="true" />
          Reset
        </button>
      </div>
    </div>

    <div v-if="error" class="card">
      <ErrorState :message="error" @retry="loadUnits" />
    </div>

    <div v-else class="card overflow-hidden">
      <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div>
          <h2 class="section-title">Daftar Satuan</h2>
          <p class="mt-0.5 text-xs text-slate-500">{{ meta.total }} satuan</p>
        </div>
        <button type="button" class="btn-icon-sm" aria-label="Muat ulang satuan" :disabled="loading" @click="loadUnits">
          <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" aria-hidden="true" />
        </button>
      </div>

      <DataTable
        :columns="columns"
        :rows="units"
        :loading="loading"
        empty-title="Belum ada satuan"
        empty-message="Tambahkan satuan untuk digunakan pada barang."
      >
        <template #cell-name="{ row }">
          <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
              <Ruler class="h-4 w-4" aria-hidden="true" />
            </span>
            <span class="font-medium text-slate-800">{{ row.name }}</span>
          </div>
        </template>

        <template #cell-abbreviation="{ row }">
          <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-medium text-slate-600">
            {{ row.abbreviation }}
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
            <button type="button" class="btn-icon-sm" :aria-label="`Edit satuan ${row.name}`" title="Edit" @click="openEdit(row)">
              <Pencil class="h-4 w-4" aria-hidden="true" />
            </button>
            <button
              type="button"
              class="btn-icon-sm"
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
              class="btn-icon-sm hover:bg-red-50 hover:text-red-700"
              :aria-label="`Hapus satuan ${row.name}`"
              :title="row.products_count > 0 ? 'Tidak dapat dihapus karena masih digunakan barang' : 'Hapus'"
              :disabled="row.products_count > 0"
              @click="deleteTarget = row"
            >
              <Trash2 class="h-4 w-4" aria-hidden="true" />
            </button>
          </div>
        </template>

        <template #empty>
          <EmptyState
            :icon="Ruler"
            :title="hasFilters ? 'Satuan tidak ditemukan' : 'Belum ada satuan'"
            :message="hasFilters ? 'Coba ubah kata kunci atau status filter.' : 'Tambahkan satuan pertama untuk digunakan pada barang.'"
          >
            <template #action>
              <button v-if="hasFilters" type="button" class="btn btn-secondary" @click="resetFilters">
                Reset Filter
              </button>
              <button v-else type="button" class="btn btn-primary" @click="openCreate">
                <Plus class="h-4 w-4" aria-hidden="true" />
                Tambah Satuan
              </button>
            </template>
          </EmptyState>
        </template>
      </DataTable>

      <Pagination v-if="!loading && units.length > 0" :meta="meta" @update:page="changePage" />
    </div>

    <AppModal
      v-model="modalOpen"
      :title="editingUnit ? 'Edit Satuan' : 'Tambah Satuan'"
      description="Nama dan singkatan harus unik."
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

      <form id="unit-form" class="space-y-4" novalidate @submit.prevent="saveUnit">
        <FormField label="Nama Satuan" for-id="unit-name" required :error="formErrors.name">
          <input
            id="unit-name"
            v-model="form.name"
            type="text"
            maxlength="50"
            class="field-input"
            :class="{ 'field-input-error': formErrors.name }"
            placeholder="Contoh: Meter"
          />
        </FormField>

        <FormField
          label="Singkatan"
          for-id="unit-abbreviation"
          required
          :error="formErrors.abbreviation"
          hint="Contoh: pcs, set, m, roll."
        >
          <input
            id="unit-abbreviation"
            v-model="form.abbreviation"
            type="text"
            maxlength="20"
            class="field-input font-mono"
            :class="{ 'field-input-error': formErrors.abbreviation }"
            placeholder="m"
          />
        </FormField>

        <ToggleSwitch
          v-model="form.is_active"
          label="Satuan Aktif"
          description="Satuan nonaktif tidak dapat dipilih untuk barang baru."
          aria-label="Ubah status satuan"
        />
      </form>

      <template #footer>
        <button type="button" class="btn btn-secondary" :disabled="saving" @click="modalOpen = false">Batal</button>
        <button type="submit" form="unit-form" class="btn btn-primary" :disabled="saving">
          <LoadingSpinner v-if="saving" />
          {{ saving ? 'Menyimpan...' : editingUnit ? 'Simpan Perubahan' : 'Tambah Satuan' }}
        </button>
      </template>
    </AppModal>

    <ConfirmationDialog
      :model-value="Boolean(deleteTarget)"
      title="Hapus satuan?"
      :message="`Satuan “${deleteTarget?.name || ''}” akan dihapus permanen.`"
      confirm-label="Ya, Hapus"
      variant="danger"
      :loading="deleting"
      @update:model-value="deleteTarget = null"
      @confirm="deleteUnit"
    />
  </div>
</template>
