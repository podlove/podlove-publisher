<template>
<<<<<<< HEAD
  <section class="my-4 font-sans">
=======
  <section class="my-4">
>>>>>>> 6ca060a4744249c97d016dd3c3b420a4285881e3
    <label class="block text-lg mb-2" :for="`podlove-module-${name}`">{{ title }}</label>
    <div :id="`podlove-module-${name}`" class="rounded-sm"><slot></slot></div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  props: {
    name: {
      type: String,
      default: null
    },
    title: {
      type: String,
      default: null
    }
  }
})
</script>

<style>
</style>
