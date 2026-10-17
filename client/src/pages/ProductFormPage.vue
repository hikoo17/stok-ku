<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { ArrowLeft, CircleAlert, Info, Save } from 'lucide-vue-next'
import PageHeader from '../components/PageHeader.vue'
import FormField from '../components/FormField.vue'
import SelectField from '../components/SelectField.vue'
import LoadingState from '../components/LoadingState.vue'
import ErrorState from '../components/ErrorState.vue'
import { productService } from '../services/products'
import { categoryService } from '../services/categories'
import { unitService } from '../services/units'
import { useToast } from '../composables/useToast'
import { getErrorMessage, getValidationErrors } from '../composables/useApiError'
import { formatNumber } from '../composables/useFormat'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const isEdit = computed(() => route.name === 'products.edit')
const productId = computed(() => route.params.id)

const form = reactive({
  sku: '',
  name: '',
  category_id: '',
  unit_id: '',
  purchase_price: 0,
  selling_price: 0,
  opening_stock: 0,
  minimum_stock: 5,
  description: '',
  is_active: true,
})

const errors = reactive({})
const formError = ref('')
const loading = ref(true)
const saving = ref(false)
const loadError = ref('')
const categories = ref([])
const units = ref([])
const currentProduct = ref(null)

const categoryOptions = computed(() =>
  categories.value.map((category) => ({
    value: category.id,
    label: category.is_active ? category.name : `${category.name} (nonaktif)`,
    disabled: false,
  })),
)

const unitOptions = computed(() =>
  units.value.map((unit) => ({
    value: unit.id,
    label: `${unit.name} (${unit.abbreviation})${unit.is_active ? '' : ' — nonaktif'}`,
    disabled: false,
  })),
)

const selectedUnit = computed(() => units.value.find((unit) => Number(unit.id) === Number(form.unit_id)))

function clearErrors() {
  Object.keys(errors).forEach((key) => delete errors[key])
  formError.value = ''
}

function validate() {
  clearErrors()

  if (!form.sku.trim()) {
    errors.sku = 'Kode produk wajib diisi.'
  }

  if (!form.name.trim()) {
    errors.name = 'Nama barang wajib diisi.'
  }

  if (!form.category_id) {
    errors.category_id = 'Kategori wajib dipilih.'
  }

  if (!form.unit_id) {
    errors.unit_id = 'Satuan wajib dipilih.'
  }

  const numericFields = [
    ['purchase_price', 'Harga beli'],
    ['selling_price', 'Harga jual'],
    ['minimum_stock', 'Batas stok minimum'],
  ]

  if (!isEdit.value) {
    numericFields.push(['opening_stock', 'Stok awal'])
  }

  numericFields.forEach(([field, label]) => {
    const value = Number(form[field])

    if (!Number.isFinite(value) || value < 0) {
      errors[field] = `${label} harus berupa angka nol atau lebih.`
    }
  })

  return Object.keys(errors).length === 0
}

function appendCurrentReference(options, current) {
  if (current && !options.some((item) => Number(item.id) === Number(current.id))) {
    return [
      ...options,
      {
        id: current.id,
        name: current.name,
        abbreviation: current.abbreviation,
        is_active: false,
      },
    ]
  }

  return options
}

async function loadForm() {
  loading.value = true
  loadError.value = ''

  try {
    const requests = [categoryService.options(), unitService.options()]

    if (isEdit.value) {
      requests.push(productService.get(productId.value))
    }

    const [categoryResponse, unitResponse, productResponse] = await Promise.all(requests)

    categories.value = categoryResponse.data.data
    units.value = unitResponse.data.data

    if (productResponse) {
      const product = productResponse.data.data
      currentProduct.value = product
      categories.value = appendCurrentReference(categories.value, product.category)
      units.value = appendCurrentReference(units.value, product.unit)

      Object.assign(form, {
        sku: product.sku,
        name: product.name,
        category_id: product.category?.id || '',
        unit_id: product.unit?.id || '',
        purchase_price: Number(product.purchase_price),
        selling_price: Number(product.selling_price),
        minimum_stock: Number(product.minimum_stock),
        description: product.description || '',
        is_active: product.is_active,
      })
    }
  } catch (requestError) {
    loadError.value = getErrorMessage(requestError, 'Data form barang gagal dimuat.')
  } finally {
    loading.value = false
  }
}

async function submit() {
  if (saving.value || !validate()) {
    return
  }
  saving.value = true
  formError.value = ''

  const payload = {
    sku: form.sku.trim(),
    name: form.name.trim(),
    category_id: Number(form.category_id),
    unit_id: Number(form.unit_id),
    purchase_price: Number(form.purchase_price) || 0,
    selling_price: Number(form.selling_price) || 0,
    minimum_stock: Number(form.minimum_stock) || 0,
    description: form.description.trim() || null,
    is_active: form.is_active,
  }

  if (!isEdit.value) {
    payload.opening_stock = Number(form.opening_stock) || 0
  }

  try {
    const response = isEdit.value
      ? await productService.update(productId.value, payload)
      : await productService.create(payload)

    toast.success(response.data.message || 'Barang berhasil disimpan.')
    await router.replace({ name: 'products.show', params: { id: response.data.data.id } })
  } catch (requestError) {
    Object.assign(errors, getValidationErrors(requestError))
    formError.value = getErrorMessage(requestError, 'Barang gagal disimpan. Periksa kembali data yang diisi.')
  } finally {
    saving.value = false
  }
}

onMounted(loadForm)
</script>

<template>
  <div>
    <PageHeader
      :title="isEdit ? 'Edit Barang' : 'Tambah Barang'"
      :description="
        isEdit
          ? 'Perbarui informasi barang tanpa mengubah stok secara langsung.'
          : 'Daftarkan barang baru beserta stok awal dan batas minimumnya.'
      "
      :breadcrumbs="[
        { label: 'Data Barang', to: '/products' },
        { label: isEdit ? 'Edit Barang' : 'Tambah Barang' },
      ]"
    >
      <template #actions>
        <RouterLink to="/products" class="btn btn-secondary btn-sm">
          <ArrowLeft class="h-4 w-4" aria-hidden="true" />
          Kembali
        </RouterLink>
      </template>
    </PageHeader>

    <div v-if="loading" class="card">
      <LoadingState text="Memuat form barang..." />
    </div>

    <div v-else-if="loadError" class="card">
      <ErrorState :message="loadError" @retry="loadForm" />
    </div>

    <form v-else class="grid grid-cols-1 gap-6 lg:grid-cols-3" novalidate @submit.prevent="submit">
      <div class="space-y-6 lg:col-span-2">
        <div
          v-if="formError"
          class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
          role="alert"
        >
          {{ formError }}
        </div>

        <section class="card">
          <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <h2 class="section-title">Informasi Barang</h2>
            <p class="mt-0.5 text-xs text-slate-500">Identitas dan pengelompokan barang.</p>
          </div>

          <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
            <FormField label="Kode Produk" for-id="sku" required :error="errors.sku" hint="Contoh: RH-001. Harus unik.">
              <input
                id="sku"
                v-model="form.sku"
                type="text"
                maxlength="50"
                class="field-input font-mono"
                :class="{ 'field-input-error': errors.sku }"
                placeholder="RH-001"
                :aria-invalid="Boolean(errors.sku)"
              />
            </FormField>

            <FormField label="Nama Barang" for-id="name" required :error="errors.name">
              <input
                id="name"
                v-model="form.name"
                type="text"
                maxlength="150"
                class="field-input"
                :class="{ 'field-input-error': errors.name }"
                placeholder="Radiator Hose 1.5 Inch"
                :aria-invalid="Boolean(errors.name)"
              />
            </FormField>

            <SelectField
              id="category_id"
              v-model="form.category_id"
              label="Kategori"
              :options="categoryOptions"
              placeholder="Pilih kategori"
              required
              :error="errors.category_id"
              hint="Kategori nonaktif tidak dapat dipilih untuk barang baru."
            />

            <SelectField
              id="unit_id"
              v-model="form.unit_id"
              label="Satuan"
              :options="unitOptions"
              placeholder="Pilih satuan"
              required
              :error="errors.unit_id"
            />

            <div class="sm:col-span-2">
              <FormField label="Deskripsi" for-id="description" :error="errors.description" hint="Opsional. Maksimal 2.000 karakter.">
                <textarea
                  id="description"
                  v-model="form.description"
                  rows="3"
                  maxlength="2000"
                  class="field-input resize-y"
                  :class="{ 'field-input-error': errors.description }"
                  placeholder="Keterangan tambahan tentang barang..."
                />
              </FormField>
            </div>
          </div>
        </section>

        <section class="card">
          <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <h2 class="section-title">Harga</h2>
            <p class="mt-0.5 text-xs text-slate-500">
              Harga ini menjadi nilai awal saat mencatat transaksi.
            </p>
          </div>

          <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
            <FormField label="Harga Beli" for-id="purchase_price" required :error="errors.purchase_price">
              <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-medium text-slate-500">
                  Rp
                </span>
                <input
                  id="purchase_price"
                  v-model.number="form.purchase_price"
                  type="number"
                  min="0"
                  step="0.01"
                  class="field-input pl-9 text-right"
                  :class="{ 'field-input-error': errors.purchase_price }"
                  placeholder="0"
                />
              </div>
            </FormField>

            <FormField label="Harga Jual" for-id="selling_price" required :error="errors.selling_price">
              <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-medium text-slate-500">
                  Rp
                </span>
                <input
                  id="selling_price"
                  v-model.number="form.selling_price"
                  type="number"
                  min="0"
                  step="0.01"
                  class="field-input pl-9 text-right"
                  :class="{ 'field-input-error': errors.selling_price }"
                  placeholder="0"
                />
              </div>
            </FormField>
          </div>
        </section>

        <section class="card">
          <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <h2 class="section-title">Persediaan</h2>
            <p class="mt-0.5 text-xs text-slate-500">
              Stok awal dicatat sebagai transaksi barang masuk yang dapat diaudit.
            </p>
          </div>

          <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
            <FormField
              v-if="!isEdit"
              label="Stok Awal"
              for-id="opening_stock"
              :error="errors.opening_stock"
              :hint="`Isi 0 jika barang belum memiliki stok.${selectedUnit ? ` Satuan: ${selectedUnit.abbreviation}.` : ''}`"
            >
              <input
                id="opening_stock"
                v-model.number="form.opening_stock"
                type="number"
                min="0"
                step="0.001"
                class="field-input text-right"
                :class="{ 'field-input-error': errors.opening_stock }"
                placeholder="0"
              />
            </FormField>

            <div v-else class="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Stok Saat Ini</p>
              <p class="mt-1 text-2xl font-semibold text-slate-900">
                {{ formatNumber(currentProduct?.current_stock) }}
                <span class="text-sm font-normal text-slate-500">{{ currentProduct?.unit?.abbreviation }}</span>
              </p>
              <p class="mt-1 text-xs text-slate-500">
                Stok hanya dapat berubah melalui transaksi barang masuk atau keluar.
              </p>
              <div class="mt-3 flex flex-wrap gap-2">
                <RouterLink to="/stock-in" class="link text-xs">Barang Masuk</RouterLink>
                <span class="text-slate-300">|</span>
                <RouterLink to="/stock-out" class="link text-xs">Barang Keluar</RouterLink>
              </div>
            </div>

            <FormField
              label="Batas Stok Minimum"
              for-id="minimum_stock"
              required
              :error="errors.minimum_stock"
              hint="Barang dianggap menipis jika stok lebih dari 0 dan kurang dari atau sama dengan batas ini."
            >
              <input
                id="minimum_stock"
                v-model.number="form.minimum_stock"
                type="number"
                min="0"
                step="0.001"
                class="field-input text-right"
                :class="{ 'field-input-error': errors.minimum_stock }"
                placeholder="5"
              />
            </FormField>
          </div>
        </section>
      </div>

      <div class="space-y-6">
        <section class="card p-5 sm:p-6">
          <h2 class="section-title">Status Barang</h2>
          <p class="mt-1 text-sm text-slate-500">
            Barang nonaktif tetap terlihat di riwayat dan laporan, tetapi tidak dapat ditransaksikan.
          </p>

          <div class="mt-4 flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-4">
            <div>
              <p class="text-sm font-medium text-slate-800">
                {{ form.is_active ? 'Barang aktif' : 'Barang diarsipkan' }}
              </p>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ form.is_active ? 'Dapat dipilih pada transaksi.' : 'Tidak dapat dipilih pada transaksi.' }}
              </p>
            </div>
            <button
              type="button"
              role="switch"
              :aria-checked="form.is_active"
              aria-label="Ubah status barang"
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
        </section>

        <section class="card border-indigo-100 bg-indigo-50/50 p-5 sm:p-6">
          <div class="flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
              <Info class="h-4 w-4" aria-hidden="true" />
            </span>
            <div>
              <h2 class="text-sm font-semibold text-slate-900">Catatan</h2>
              <ul class="mt-2 space-y-2 text-xs leading-5 text-slate-600">
                <li class="flex gap-2">
                  <CircleAlert class="mt-0.5 h-3.5 w-3.5 shrink-0 text-indigo-500" aria-hidden="true" />
                  Kode produk harus unik dan mudah dikenali.
                </li>
                <li class="flex gap-2">
                  <CircleAlert class="mt-0.5 h-3.5 w-3.5 shrink-0 text-indigo-500" aria-hidden="true" />
                  Stok awal hanya dapat diisi saat barang pertama kali dibuat.
                </li>
                <li class="flex gap-2">
                  <CircleAlert class="mt-0.5 h-3.5 w-3.5 shrink-0 text-indigo-500" aria-hidden="true" />
                  Harga transaksi dapat berbeda dari harga master bila diperlukan.
                </li>
              </ul>
            </div>
          </div>
        </section>

        <div class="card flex flex-col gap-2 p-4 sm:flex-row-reverse sm:p-5">
          <button type="submit" class="btn btn-primary flex-1" :disabled="saving">
            <span
              v-if="saving"
              class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
              aria-hidden="true"
            />
            <Save v-else class="h-4 w-4" aria-hidden="true" />
            {{ saving ? 'Menyimpan...' : 'Simpan Barang' }}
          </button>
          <RouterLink to="/products" class="btn btn-secondary flex-1">Batal</RouterLink>
        </div>
      </div>
    </form>
  </div>
</template>
