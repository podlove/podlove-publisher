<template>
  <module name="description" title="Episode Description">
    <div class="p-3">
      <div class="block md:flex mb-2">
        <episode-number class="w-full md:w-2/12 mr-5 mb-5" />
        <episode-title class="w-full md:w-10/12 mb-5" />
      </div>
      <div class="mb-5">
        <episode-subtitle class="w-full" />
      </div>
      <div>
        <episode-summary class="w-full" />
      </div>
    </div>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { injectStore } from 'redux-vuex'

import Module from '@components/module/Module.vue'
import * as episode from '@store/episode.store'

import EpisodeNumber from './components/EpisodeNumber.vue'
import EpisodeTitle from './components/EpisodeTitle.vue'
import EpisodeSubtitle from './components/EpisodeSubtitle.vue'
import EpisodeSummary from './components/EpisodeSummary.vue'

export default defineComponent({
  components: {
    Module,
    EpisodeNumber,
    EpisodeTitle,
    EpisodeSubtitle,
    EpisodeSummary
  },

  setup(): { dispatch: Function } {
    return {
      dispatch: injectStore().dispatch,
    }
  },

  created() {
    this.dispatch(episode.init())
  },
})
</script>

<style>
</style>
