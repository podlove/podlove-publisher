<template>
    <div>
      <label for="episode-license-name" class="block text-sm font-medium text-gray-700">{{ __('License name') }}</label>
      <div class="mt-1">
        <input
          name="episode-license-name"
          type="text"
          :value="state.episodeLicenseName"
          @input="updateLicenseName"
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
        episodeLicenseName: selectors.episode.license_name,
      }),
      dispatch: injectStore().dispatch,
    }
  },
  created() {
    this.dispatch(episode.init())
  },
  methods: {
    updateLicenseName(event: Event) {
      this.dispatch(
        episode.update({prop: 'license_name', value: (event.target as HTMLInputElement).value})
      )
    }
  }
})
</script>



<style>
</style>