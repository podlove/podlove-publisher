<template>
    <podlove-button variant="secondary" size="small" @click="clearSoundbite()">{{ __('Clear', 'podlove-podcasting-plugin-for-wordpress') }}</podlove-button>
</template>

<script lang="ts">
import { defineComponent } from '@vue/runtime-core'

import { update as updateEpisode } from '@store/episode.store'
import { injectAppDispatch } from '@store/vue'

import PodloveButton from '@components/button/Button.vue'

export default defineComponent({
  components: { PodloveButton },

  setup() {
    return {
      dispatch: injectAppDispatch(),
    }
  },

  methods: {
    clearSoundbite() {
      this.dispatch(
        updateEpisode({prop: 'soundbite_start', value: null })
      )
      this.dispatch(
        updateEpisode({prop: 'soundbite_duration', value: null }),
      )
      this.dispatch(
        updateEpisode({prop: 'soundbite_title', value: null })
      )
    }
  },

})

</script>

<style lang="postcss">
</style>
