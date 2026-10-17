<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ChartNoAxesColumn,
  CheckCircle2,
  Eye,
  EyeOff,
  Lock,
  Mail,
  Package,
  ShieldCheck,
} from 'lucide-vue-next'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'
import { getErrorMessage, getValidationErrors } from '../composables/useApiError'

const router = useRouter()
const route = useRoute()
const auth = useAuth()
const toast = useToast()

const form = reactive({
  email: '',
  password: '',
})

const errors = reactive({})
const formError = ref('')
const loading = ref(false)
const showPassword = ref(false)
const isDev = import.meta.env.DEV

const redirectTarget = computed(() => {
  const target = route.query.redirect

  return typeof target === 'string' && target.startsWith('/') ? target : '/'
})

async function submit() {
  if (loading.value) {
    return
  }

  Object.keys(errors).forEach((key) => delete errors[key])
  formError.value = ''

  if (!form.email.trim()) {
    errors.email = 'Email wajib diisi.'
  }

  if (!form.password) {
    errors.password = 'Kata sandi wajib diisi.'
  }

  if (Object.keys(errors).length > 0) {
    return
  }

  loading.value = true

  try {
    await auth.login({
      email: form.email.trim(),
      password: form.password,
    })

    toast.success('Selamat datang kembali!')
    await router.replace(redirectTarget.value)
  } catch (error) {
    Object.assign(errors, getValidationErrors(error))
    formError.value = getErrorMessage(error, 'Gagal masuk. Periksa email dan kata sandi Anda.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="grid min-h-screen bg-white lg:grid-cols-2">
    <section class="relative hidden overflow-hidden bg-indigo-700 p-10 text-white lg:flex lg:flex-col xl:p-14">
      <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full border border-white/10" aria-hidden="true" />
      <div class="absolute -bottom-32 -left-20 h-96 w-96 rounded-full border border-white/10" aria-hidden="true" />

      <div class="relative flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
          <Package class="h-6 w-6" aria-hidden="true" />
        </div>
        <div>
          <p class="text-base font-semibold">Stok Ku</p>
          <p class="text-xs text-indigo-200">Manajemen Persediaan</p>
        </div>
      </div>

      <div class="relative my-auto max-w-lg py-12">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-200">
          Pengelolaan persediaan
        </p>
        <h1 class="mt-4 text-3xl font-semibold leading-tight xl:text-4xl">
          Kelola stok barang lebih rapi dan terpercaya.
        </h1>
        <p class="mt-4 text-sm leading-6 text-indigo-100">
          Catat barang masuk dan keluar, pantau stok menipis, serta siapkan laporan persediaan
          dalam satu tempat.
        </p>

        <ul class="mt-8 space-y-4 text-sm">
          <li class="flex items-start gap-3">
            <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-emerald-300" aria-hidden="true" />
            <span>Riwayat transaksi tersimpan rapi dan dapat ditelusuri.</span>
          </li>
          <li class="flex items-start gap-3">
            <ShieldCheck class="mt-0.5 h-5 w-5 shrink-0 text-emerald-300" aria-hidden="true" />
            <span>Stok tidak dapat menjadi negatif saat barang keluar.</span>
          </li>
          <li class="flex items-start gap-3">
            <ChartNoAxesColumn class="mt-0.5 h-5 w-5 shrink-0 text-emerald-300" aria-hidden="true" />
            <span>Laporan stok dan pergerakan barang siap diunduh.</span>
          </li>
        </ul>
      </div>

      <p class="relative text-xs text-indigo-200">
        &copy; {{ new Date().getFullYear() }} Stok Ku. Aplikasi persediaan untuk usaha Anda.
      </p>
    </section>

    <section class="flex flex-col justify-center px-5 py-10 sm:px-10 lg:px-16">
      <div class="mx-auto w-full max-w-md">
        <div class="mb-8 flex items-center gap-3 lg:hidden">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-white">
            <Package class="h-5 w-5" aria-hidden="true" />
          </div>
          <div>
            <p class="text-sm font-semibold text-slate-900">Stok Ku</p>
            <p class="text-xs text-slate-500">Manajemen Persediaan</p>
          </div>
        </div>

        <div>
          <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Masuk ke akun Anda</h2>
          <p class="mt-2 text-sm text-slate-500">
            Gunakan email dan kata sandi untuk mengakses aplikasi.
          </p>
        </div>

        <div
          v-if="formError"
          class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
          role="alert"
        >
          {{ formError }}
        </div>

        <form class="mt-6 space-y-5" novalidate @submit.prevent="submit">
          <div>
            <label for="email" class="field-label">Email</label>
            <div class="relative">
              <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                <Mail class="h-4 w-4" aria-hidden="true" />
              </span>
              <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                autofocus
                class="field-input pl-9"
                :class="{ 'field-input-error': errors.email }"
                placeholder="nama@perusahaan.com"
                :aria-invalid="Boolean(errors.email)"
                :aria-describedby="errors.email ? 'email-error' : undefined"
              />
            </div>
            <p v-if="errors.email" id="email-error" class="field-error">{{ errors.email }}</p>
          </div>

          <div>
            <label for="password" class="field-label">Kata Sandi</label>
            <div class="relative">
              <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                <Lock class="h-4 w-4" aria-hidden="true" />
              </span>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                class="field-input px-9"
                :class="{ 'field-input-error': errors.password }"
                placeholder="Masukkan kata sandi"
                :aria-invalid="Boolean(errors.password)"
                :aria-describedby="errors.password ? 'password-error' : undefined"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 transition hover:text-slate-600"
                :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                @click="showPassword = !showPassword"
              >
                <EyeOff v-if="showPassword" class="h-4 w-4" aria-hidden="true" />
                <Eye v-else class="h-4 w-4" aria-hidden="true" />
              </button>
            </div>
            <p v-if="errors.password" id="password-error" class="field-error">{{ errors.password }}</p>
          </div>

          <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            <span
              v-if="loading"
              class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
              aria-hidden="true"
            />
            {{ loading ? 'Memproses...' : 'Masuk' }}
          </button>
        </form>

        <div
          v-if="isDev"
          class="mt-6 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500"
        >
          <p class="font-medium text-slate-700">Akun demo pengembangan</p>
          <p class="mt-1">
            Email: <span class="font-mono text-slate-700">admin@stokku.test</span><br />
            Kata sandi: <span class="font-mono text-slate-700">password</span>
          </p>
          <p class="mt-2 text-[11px] text-slate-500">
            Akun ini hanya tersedia pada lingkungan pengembangan.
          </p>
        </div>
      </div>
    </section>
  </div>
</template>
