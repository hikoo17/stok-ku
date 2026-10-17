<script setup>
import Skeleton from './Skeleton.vue'
</script>

<template>
  <div class="card flex h-full min-h-[112px] items-start gap-4 p-5" aria-hidden="true">
    <Skeleton width="2.75rem" height="2.75rem" class="shrink-0 rounded-lg" />
    <div class="min-w-0 flex-1 space-y-2.5">
      <Skeleton width="55%" height="0.75rem" />
      <Skeleton width="35%" height="1.5rem" />
      <Skeleton width="75%" height="0.65rem" />
    </div>
  </div>
</template>
