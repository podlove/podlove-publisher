<template>
  <div class="hidden" :data-tab="name">
    <slot></slot>
  </div>
</template>

<script type="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  data() {
    return {
      visible: false
    }
  },

  props: {
    name: String
  },

  methods: {
  }
})
</script>
