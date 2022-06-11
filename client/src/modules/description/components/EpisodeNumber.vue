<template>
  <div>
    <label for="episode-number" class="block text-sm font-medium text-gray-700">{{ $t('episode.number.label') }}</label>
    <div class="mt-1 mb-1">
      <input
        name="episode-number"
        type="number"
        class="
          shadow-sm
          focus:ring-indigo-500 focus:border-indigo-500
          block
          w-full
          sm:text-sm
          border-gray-300
          rounded-md
        "
        :value="state.number"
        @input="updateNumber($event)"
      />
    </div>
   <p class="mt-2 text-sm text-gray-500">{{ $t('episode.number.description') }}</p>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'

import { selectors } from '@store'
import { update as updateEpisode } from '@store/episode.store'


import Module from '@components/module/Module.vue'

export default defineComponent({
  components: {
    Module,
  },

  setup() {
    return {
      state: mapState({
        number: selectors.episode.number,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    updateNumber(event: Event) {
      this.dispatch(
        updateEpisode({ prop: 'number', value: (event.target as HTMLInputElement).value })
      )
    },
  },
})
</script>



<style>
</style>
