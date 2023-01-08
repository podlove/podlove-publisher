<template>
    <podlove-button variant="secondary" size="small" @click="clearSoundbite()">Clear</podlove-button>
</template>

<script lang="ts">
import { injectStore, mapState } from 'redux-vuex'
import { defineComponent } from '@vue/runtime-core'

import { update as updateEpisode } from '@store/episode.store'

import PodloveButton from '@components/button/Button.vue'

export default defineComponent({
  components: {
    PodloveButton,
  },

  data() {
      return {}
  },

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
