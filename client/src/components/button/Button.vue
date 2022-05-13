<template>
  <button
    type="button"
    class="
      inline-flex
      items-center
      focus:outline-none focus:ring-2
      border border-transparent
      shadow-sm
      whitespace-nowrap
      disabled:opacity-75
    "
    :disabled="disabled"
    :class="[variantClass, sizeClass]"
  >
    <slot />
  </button>
</template>

<script lang="ts">
import { defineComponent, PropType } from '@vue/runtime-core'

export type ButtonType = 'primary' | 'secondary' | 'submit' | 'danger' | 'default'
export type ButtonSize = 'small' | 'medium' | 'large'

export default defineComponent({
  props: {
    variant: {
      type: String as PropType<ButtonType>,
      default: 'default',
    },
    size: {
      type: String as PropType<ButtonSize>,
      default: 'medium',
    },
    disabled: {
      type: Boolean,
      default: false,
    },
  },

  computed: {
    variantClass() {
      switch (this.variant) {
        case 'default':
          return `focus:outline-none text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-gray-100`
        case 'primary':
          return `focus:ring-offset-2 text-white focus:ring-indigo-500 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300`
        case 'primary-disabled':
          return `focus:ring-offset-2 text-white focus:ring-indigo-500 bg-indigo-600 opacity-50 cursor-not-allowed`
        case 'secondary':
          return `focus:ring-offset-2 text-indigo-700 focus:ring-indigo-500 bg-indigo-100 hover:bg-indigo-200 disabled:bg-indigo-50`
        case 'danger':
          return `bg-red-600 text-white hover:bg-red-700 focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:bg-red-300`
      }
    },

    sizeClass() {
      switch (this.size) {
        case 'small':
          return `px-2.5 py-1.5 text-xs font-medium rounded `
        case 'medium':
          return `px-3 py-2 text-sm leading-4 font-medium rounded-md`
        case 'large':
          return `px-6 py-3 text-base font-medium rounded-md`
      }
    },
  },
})
</script>

<style></style>
