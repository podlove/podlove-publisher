<template>
  <form class="space-y-8 divide-y divide-gray-200">
    <div class="space-y-8 divide-y divide-gray-200">
      <div>
        <div>
          <h3 class="text-lg leading-6 font-medium text-gray-900">Manage Production</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ production?.metadata?.title }}
            <a
              :href="production?.edit_page"
              target="_blank"
              class="cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
              >edit in Auphonic</a
            >
          </p>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
          <div class="sm:col-span-4">
            <label class="block text-sm font-medium text-gray-700"> Audio Source </label>
            <div class="mt-1 flex">
              <FileChooser />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="pt-5">
      <div class="flex justify-end gap-3">
        <podlove-button variant="secondary">Cancel</podlove-button>
        <podlove-button variant="primary">Start Production</podlove-button>
      </div>
    </div>
  </form>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

import PodloveButton from '@components/button/Button.vue'
import FileChooser from './FileChooser.vue'

import { selectors } from '@store'

import { injectStore, mapState } from 'redux-vuex'
import * as auphonic from '@store/auphonic.store'

export default defineComponent({
  components: {
    PodloveButton,
    FileChooser,
  },

  setup() {
    return {
      state: mapState({
        production: selectors.auphonic.production,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  computed: {
    production(): string {
      return this.state.production || {}
    },
  },
})
</script>
