<template>
  <div>
    <label for="episode-license-name" class="block text-sm font-medium text-gray-700">{{ __('License url') }}</label>
    <div class="mt-1">
      <input 
        name="episode-license-name"
        type="text"
        :value="state.episodeLicenseUrl"
        @input="updateLicenseUrl"
        class="
          shadow-sm
          focus:ring-indigo-500 focus:border-indigo-500
          block
          w-full
          sm:text-sm
          border-gray-300
          rounded-md
        "
      />
    </div>
    <p class="mt-2 text-sm text-gray-500">{{ __('Example: http://creativecommons.org/licenses/by/3.0/') }}</p>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex';

import { selectors } from '@store';

import Module from '@components/module/Module.vue'
import * as episode from '@store/episode.store'

export default defineComponent({
  components: {
    Module,
  },
  setup() {
    return {
      state: mapState({
        episodeLicenseUrl: selectors.episode.license_url,
      }),
      dispatch: injectStore().dispatch,
    }
  },
  created() {
    this.dispatch(episode.init())
  },
  methods: {
    updateLicenseUrl(event: Event) {
      this.dispatch(
        episode.update({prop: 'license_url', value: (event.target as HTMLInputElement).value})
      )
    }
  }
})
</script>



<style>
</style>