<script setup>
import { onBeforeUnmount, onMounted } from 'vue'
import { RouterView, useRouter } from 'vue-router'
import ToastContainer from './components/ToastContainer.vue'
import { useAuth } from './composables/useAuth'

const router = useRouter()
const { clearSession } = useAuth()

function handleUnauthorized() {
  clearSession()

  if (router.currentRoute.value.name !== 'login') {
    router.replace({
      name: 'login',
      query: { redirect: router.currentRoute.value.fullPath },
    })
  }
}

onMounted(() => window.addEventListener('stokku:unauthorized', handleUnauthorized))
onBeforeUnmount(() => window.removeEventListener('stokku:unauthorized', handleUnauthorized))
</script>

<template>
  <RouterView />
  <ToastContainer />
</template>
