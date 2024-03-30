<template>
  <div>
    <label for="subtitle" class="block text-sm font-medium text-gray-700">{{
      __('Subtitle', 'podlove-podcasting-plugin-for-wordpress')
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
      <span>{{ __('Single sentence describing the episode.', 'podlove-podcasting-plugin-for-wordpress') }}</span>
      <span>{{ charactersLeft }}</span>
    </p>
  </div>
</template>

<script lang="ts">
import { injectStore, mapState } from 'redux-vuex'
import { computed, defineComponent } from 'vue'

import { update as updateEpisode } from '@store/episode.store'
import { selectors } from '@store'

export default defineComponent({
  setup() {
    const state =  mapState({
        subtitle: selectors.episode.subtitle,
      })

    const charactersLeft = computed(() => 255 - (state?.subtitle?.length || ''));

    return {
      state,
      charactersLeft,
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    updateSubtitle(event: Event) {
      this.dispatch(
        updateEpisode({ prop: 'subtitle', value: (event.target as HTMLInputElement).value })
      )
    },
  }
})
</script>
