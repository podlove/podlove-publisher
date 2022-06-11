<template>
  <div>
    <label for="summary" class="block text-sm font-medium text-gray-700">{{
      $t('episode.summary.label')
    }}</label>
    <div class="mt-1">
      <textarea
        id="summary"
        name="summary"
        maxlength="4000"
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
        :value="state.summary"
        @input="updateSummary($event)"
      ></textarea>
    </div>
    <p class="mt-2 text-sm text-gray-500 flex justify-end">
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
        summary: selectors.episode.summary,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    updateSummary(event: Event) {
      this.dispatch(
        updateEpisode({ prop: 'summary', value: (event.target as HTMLInputElement).value })
      )
    },
  },

  computed: {
    charactersLeft() {
      return 4000 - (this.state.summary || '').length
    }
  }
})
</script>
