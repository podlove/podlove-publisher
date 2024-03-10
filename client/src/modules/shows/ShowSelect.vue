<template>
  <div>
    <div class="flex items-center">
      <input
        id="podlove-show-default"
        name="podlove-episode-show"
        type="radio"
        class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-600"
        :value="null"
        @input="setShow(null)"
        :checked="current === ''"
      />
      <label for="podlove-show-default" class="ml-2 block text-sm leading-6 text-gray-900">{{
        __('Podcast (no show assignment)')
      }}</label>
    </div>
    <div v-for="show in shows" class="flex items-center">
      <input
        :id="'podlove-show-' + show.slug"
        name="podlove-episode-show"
        type="radio"
        class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-600"
        :value="show.id"
        @input="setShow(show.slug)"
        :checked="show.slug === current"
      />
      <label
        :for="'podlove-show-' + show.slug"
        class="ml-2 block text-sm leading-6 text-gray-900"
        >{{ show.title }}</label
      >
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

import { injectStore, mapState } from 'redux-vuex'

import * as shows from '@store/shows.store'
import { update as updateEpisode } from '@store/episode.store'
import { selectors } from '@store'
import { PodloveShow } from '../../types/shows.types'

export default defineComponent({
  components: {},

  setup() {
    return {
      state: mapState({
        shows: selectors.shows.list,
        current: selectors.episode.currentShow,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  computed: {
    shows(): PodloveShow[] {
      return this.state.shows
    },
    current(): string | null {
      return this.state.current || ''
    },
  },

  methods: {
    setShow(show: string | null): void {
      this.dispatch(updateEpisode({ prop: 'show', value: show ?? '' }))
    },
  },

  created() {
    this.dispatch(shows.init())
  },
})
</script>

<style scoped>
div > input[type='radio']:checked::before {
  background-color: white;
}
</style>
