<template>
  <div class="relative flex items-start">
    <div class="flex items-center h-5">
      <input
        id="explicit-content"
        type="checkbox"
        class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
        :value="state.explicit"
        @input="updateExplicit($event)"
      />
    </div>
    <div class="ml-3 text-sm">
      <label for="explicit-content" class="font-medium text-gray-700">{{ __('Explicit Content?') }}</label>
      <p class="text-gray-500">
        {{ __('for example, profanity or content that may not be suitable for children') }}
      </p>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'

import { selectors } from '@store'
import { update as updateEpisode } from '@store/episode.store'

export default defineComponent({
  setup() {
    return {
      state: mapState({
        explicit: selectors.episode.explicit,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    updateExplicit(event: Event) {
      this.dispatch(
        updateEpisode({ prop: 'explicit', value: (event.target as HTMLInputElement).value })
      )
    },
  },
})
</script>



<style>
</style>
