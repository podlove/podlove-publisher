<template>
  <div>
    <Popper hover openDelay="200" closeDelay="100" :arrow="true">
      <slot name="trigger" />

      <template #content>
        <div class="bg-white p-4 rounded shadow-lg max-w-md">
          <slot name="content" />
        </div>
      </template>
    </Popper>
  </div>
</template>

<script lang="ts">
import { defineComponent, PropType } from '@vue/runtime-core'
import Popper from 'vue3-popper'

type TooltipDirection = 'top' | 'bottom' | 'left' | 'right'

export default defineComponent({
  components: {
    Popper,
  },

  props: {
    direction: {
      type: String as PropType<TooltipDirection>,
      default: 'left',
    },
  },

  data() {
    return {
      visible: true,
    }
  },

  computed: {
    caretClasses() {
      switch (this.direction) {
        case 'left':
          return 'left-0 -ml-2 bottom-0 top-0 h-full'
      }
    },
  },

  methods: {
    show() {
      this.visible = true
    },

    hide() {
      this.visible = false
    },
  },
})
</script>

<style lang="postcss">
</style>
