<template>
  <module name="description" title="Episode Description">
    <div class="p-3">
      <div class="flex justify-items-stretch mb-5">
        <episode-poster class="mr-5"/>
        <div class="mb-2 w-full">
          <episode-number class="w-full mb-5" />
          <episode-type class="w-full" />
        </div>
      </div>

      <div class="mb-5">
        <episode-title class="w-full" />
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

import EpisodePoster from './components/EpisodePoster.vue'
import EpisodeNumber from './components/EpisodeNumber.vue'
import EpisodeTitle from './components/EpisodeTitle.vue'
import EpisodeSubtitle from './components/EpisodeSubtitle.vue'
import EpisodeSummary from './components/EpisodeSummary.vue'
import EpisodeType from './components/EpisodeType.vue'

export default defineComponent({
  components: {
    Module,
    EpisodePoster,
    EpisodeNumber,
    EpisodeTitle,
    EpisodeSubtitle,
    EpisodeSummary,
    EpisodeType
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
