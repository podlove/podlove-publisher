<template>
  <module
    name="related-episodes"
    :title="__('Related Episodes')"
  >
    <div class="text-center h-36 flex items-center justify-center flex-col">
      <p class="mt-1 text-sm text-gray-500">{{ __('Get started by adding a related episode.') }}</p>
      <div class="mt-6">
        <podlove-button
          variant="secondary"
          size="small"
          @click="handleAddRelatedEpisode()"
        >{{ __('Add Related Episode') }}</podlove-button>
      </div>
    </div>
  </module>
</template>

<script lang="ts">
import Module from '@components/module/Module.vue'
import { defineComponent } from 'vue'
import PodloveButton from '@components/button/Button.vue'

export default defineComponent({
  components: {
    Module,
    PodloveButton,
  },
  methodes: {
    handleAddRelatedEpisode() {
      console.log('Click Add')
    },
  },
})
</script>

<style>
</style>
