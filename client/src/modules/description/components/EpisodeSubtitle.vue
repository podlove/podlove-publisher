<template>
  <div>
    <label for="subtitle" class="block text-sm font-medium text-gray-700">{{
      $t('episode.subtitle.label')
    }}</label>
    <div class="mt-1">
      <textarea
        id="subtitle"
        name="subtitle"
        maxlength="250"
        class="
          shadow-sm
          focus:ring-indigo-500 focus:border-indigo-500
          mt-1
          block
          w-full
          sm:text-sm
          border border-gray-300
          rounded-md
          resize-y
        "
        :value="state.subtitle"
        @input="updateSubtitle($event)"
      ></textarea>
    </div>
    <p class="mt-2 text-sm text-gray-500 flex justify-between">
      <span>{{ $t('episode.subtitle.description') }}</span>
      <span>{{ charactersLeft }}</span>
    </p>
  </div>
</template>

<script lang="ts">
import { injectStore, mapState } from 'redux-vuex'
import { defineComponent } from 'vue'

import { update as updateEpisode } from '@store/episode.store'
import { selectors } from '@store'

export default defineComponent({
  setup() {
    return {
      state: mapState({
        subtitle: selectors.episode.subtitle,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    updateSubtitle(event: Event) {
      this.dispatch(
        updateEpisode({ prop: 'subtitle', value: (event.target as HTMLInputElement).value })
      )
    },
  },

  computed: {
    charactersLeft() {
      return 255 - (this.state.subtitle || '').length
    }
  }
})
</script>
