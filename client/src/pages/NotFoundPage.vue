<script setup>
import { RouterLink } from 'vue-router'
import { ArrowLeft, FileQuestion } from 'lucide-vue-next'
</script>

<template>
  <div class="flex min-h-[60vh] flex-col items-center justify-center text-center">
    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
      <FileQuestion class="h-8 w-8" aria-hidden="true" />
    </div>
    <p class="mt-6 text-sm font-semibold uppercase tracking-wider text-indigo-600">404</p>
    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Halaman tidak ditemukan</h1>
    <p class="mt-2 max-w-md text-sm text-slate-500">
      Halaman yang Anda cari mungkin telah dipindahkan atau tidak tersedia.
    </p>
    <RouterLink to="/" class="btn btn-primary mt-6">
      <ArrowLeft class="h-4 w-4" aria-hidden="true" />
      Kembali ke Dashboard
    </RouterLink>
  </div>
</template>
