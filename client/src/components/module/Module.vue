<template>
  <section class="bg-white font-sans sm:rounded-lg sm:shadow-md">
    <div class="bg-white px-4 py-5 border-b border-gray-200 sm:px-6">
      <div class="-ml-4 -mt-2 flex items-center justify-between flex-wrap sm:flex-nowrap">
        <div class="ml-4 mt-2">
          <h3 class="text-lg leading-6 font-medium text-gray-900" :for="`podlove-module-${name}`">
            {{ title }}
          </h3>
        </div>
        <div class="ml-4 mt-2 flex flex-shrink-0">
          <slot name="actions"></slot>
        </div>
      </div>
    </div>
    <div class="bg-white shadow rounded-lg">
      <slot></slot>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  props: {
    name: {
      type: String,
      default: null,
    },
    title: {
      type: String,
      default: null,
    },
  },
})
</script>

<style>
</style>
