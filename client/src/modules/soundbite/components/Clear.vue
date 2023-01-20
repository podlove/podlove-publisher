<template>
    <podlove-button variant="secondary" size="small" @click="clearSoundbite()">{{ __('Clear') }}</podlove-button>
</template>

<script lang="ts">
import { injectStore } from 'redux-vuex'
import { defineComponent } from '@vue/runtime-core'

import { update as updateEpisode } from '@store/episode.store'

import PodloveButton from '@components/button/Button.vue'

export default defineComponent({
  components: { PodloveButton },

  setup() {
    return {
      dispatch: injectStore().dispatch,
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
