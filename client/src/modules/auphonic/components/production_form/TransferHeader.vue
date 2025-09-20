<template>
  <div class="mb-4">
    <h3 class="text-lg font-medium leading-6 text-gray-900">PLUS File Storage</h3>
    <p class="mt-1 text-sm text-gray-500">
      {{ __('Auphonic files are automatically transferred to PLUS storage.', 'podlove-podcasting-plugin-for-wordpress') }}
    </p>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'TransferHeader'
})
</script>
