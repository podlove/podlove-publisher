<template>
  <Popover class="relative">
    <PopoverButton>
      <slot name="trigger" />
    </PopoverButton>

    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-1 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-1 opacity-0"
    >
      <PopoverPanel class="absolute">
          <slot name="content" class="overflow-hidden"/>
      </PopoverPanel>
    </transition>
  </Popover>
</template>

<script lang="ts">
import { defineComponent } from '@vue/runtime-core'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'

export default defineComponent({
  components: {
    Popover,
    PopoverButton,
    PopoverPanel,
  }
})
</script>

<style>
</style>
