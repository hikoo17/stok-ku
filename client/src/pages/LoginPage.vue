<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Eye, EyeOff, Lock, Mail, Package } from 'lucide-vue-next'
import LoadingSpinner from '../components/LoadingSpinner.vue'
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

    toast.success('Berhasil masuk.')
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
  <main class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
    <div class="w-full max-w-sm">
      <div class="card p-6 sm:p-8">
        <div class="mb-7 flex flex-col items-center text-center">
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm">
            <Package class="h-5 w-5" aria-hidden="true" />
          </div>
          <h1 class="mt-4 text-xl font-semibold tracking-tight text-slate-900">Masuk ke Stok Ku</h1>
          <p class="mt-2 max-w-xs text-sm leading-6 text-slate-500">
            Selamat datang. Masuk untuk mengelola persediaan barang Anda.
          </p>
        </div>

        <div
          v-if="formError"
          class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
          role="alert"
        >
          {{ formError }}
        </div>

        <form class="space-y-5" novalidate @submit.prevent="submit">
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
                class="field-input pl-9 pr-10"
                :class="{ 'field-input-error': errors.password }"
                placeholder="Masukkan kata sandi"
                :aria-invalid="Boolean(errors.password)"
                :aria-describedby="errors.password ? 'password-error' : undefined"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-500 transition hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                @click="showPassword = !showPassword"
              >
                <EyeOff v-if="showPassword" class="h-4 w-4" aria-hidden="true" />
                <Eye v-else class="h-4 w-4" aria-hidden="true" />
              </button>
            </div>
            <p v-if="errors.password" id="password-error" class="field-error">{{ errors.password }}</p>
          </div>

          <button type="submit" class="btn btn-primary btn-lg mt-2 w-full" :disabled="loading">
            <LoadingSpinner v-if="loading" />
            {{ loading ? 'Memproses...' : 'Masuk' }}
          </button>
        </form>

        <div class="mt-6 border-t border-slate-100 pt-4">
          <p class="text-center text-xs text-slate-500">
            &copy; {{ new Date().getFullYear() }} Stok Ku. Seluruh hak cipta dilindungi.
          </p>
        </div>
      </div>
    </div>
  </main>
</template>
