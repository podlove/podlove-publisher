<template>
  <label
    for="mediafile_upload"
    class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5"
    >{{ __('Upload') }}</label
  >
  <div class="mt-2 sm:col-span-2 sm:mt-0">
    <div>
      <button
        type="button"
        class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-12 text-center hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
      >
        <svg
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          class="mx-auto h-12 w-12 text-gray-400"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"
          />
        </svg>

        <span class="mt-2 block text-sm font-semibold text-gray-900">Upload Media File</span>
      </button>
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
      state: mapState({}),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {},

  computed: {},
})
</script>
