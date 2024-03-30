<template>
  <module name="description" :title="__('Episode Description', 'podlove-podcasting-plugin-for-wordpress')">
    <div class="p-3">
      <div class="flex justify-items-stretch mb-5">
        <episode-poster class="mr-5"/>
        <div class="mb-2 w-full">
          <div class="flex mb-5">
            <episode-number class="w-full" />
            <episode-content v-if="state.explicitContentEnabled" class="ml-5 w-full" />
          </div>
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
import { mapState, injectStore } from 'redux-vuex'

import { selectors } from '@store'

import Module from '@components/module/Module.vue'
import * as episode from '@store/episode.store'

import EpisodePoster from './components/EpisodePoster.vue'
import EpisodeNumber from './components/EpisodeNumber.vue'
import EpisodeContent from './components/EpisodeContent.vue'
import EpisodeTitle from './components/EpisodeTitle.vue'
import EpisodeSubtitle from './components/EpisodeSubtitle.vue'
import EpisodeSummary from './components/EpisodeSummary.vue'
import EpisodeType from './components/EpisodeType.vue'

export default defineComponent({
  components: {
    Module,
    EpisodePoster,
    EpisodeNumber,
    EpisodeContent,
    EpisodeTitle,
    EpisodeSubtitle,
    EpisodeSummary,
    EpisodeType
  },

  setup() {
    return {
      state: mapState({
        explicitContentEnabled: selectors.settings.enableEpisodeExplicit,
      }),
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
