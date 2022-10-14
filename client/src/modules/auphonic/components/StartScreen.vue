<template>
  <div>
    <div class="m-12 mb-24 text-center max-w-5xl">
      <AuphonicLogo className="mx-auto h-16 w-16 text-gray-400" />

      <div class="w-full flex justify-center" v-if="isInitializing">
        <div class="animate-pulse mt-4 flex space-x-4">
          <RefreshIcon class="animate-spin h-5 w-5 mr-3" />
          Loading...
        </div>
      </div>

      <div :class="{ 'text-left': true, 'opacity-0': isInitializing }">
        <h2 class="text-lg font-medium text-gray-900">No production connected yet</h2>
        <p class="mt-1 text-sm text-gray-500">
          Manage your audio post production with Auphonic. Get started by selecting an existing
          production or create a new one from an Auphonic preset.
        </p>
        <div
          class="sm:divide-x sm:divide-gray-200 mt-6 py-6 gap-8 sm:gap-0 grid grid-cols-1 sm:grid-cols-2"
        >
          <div class="flow-root sm:px-6">
            <div
              class="relative -m-2 p-2 flex items-center justify-around space-x-4 rounded-xl hover:bg-gray-50 focus-within:ring-2 focus-within:ring-indigo-500"
            >
              <div>
                <h3 class="text-sm font-medium text-gray-900">Create New Production</h3>
                <!-- <p class="mt-1 text-sm text-gray-500">
                  Create a new production using one of your Auphonic presets.
                </p> -->
              </div>
            </div>
            <div
              class="mt-2 sm:mt-8 flex justify-center align-middle content-center items-center gap-3"
            >
              <div class="w-full max-w-md">
                <SelectPreset />
              </div>
            </div>
            <div
              class="mt-10 flex flex-col justify-center align-middle content-center items-center gap-3"
            >
              <podlove-button
                disabled="true"
                v-if="buttonState == 'idle'"
                variant="primary-disabled"
                ><plus-sm-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> Create
                Production</podlove-button
              >
              <podlove-button
                v-if="buttonState == 'single'"
                variant="primary"
                @click="createProduction"
                ><plus-sm-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> Create
                Production</podlove-button
              >
              <podlove-button
                v-if="buttonState == 'multi'"
                variant="primary"
                @click="createMultitrackProduction"
                ><plus-sm-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> Create Multitrack
                Production</podlove-button
              >
            </div>
          </div>
          <div class="flow-root sm:px-6">
            <div
              class="relative -m-2 p-2 flex items-center justify-around space-x-4 rounded-xl hover:bg-gray-50 focus-within:ring-2 focus-within:ring-indigo-500"
            >
              <div>
                <h3 class="text-sm font-medium text-gray-900">Select Production</h3>
                <!-- <p class="mt-1 text-sm text-gray-500">Select an existing Auphonic production.</p> -->
              </div>
            </div>
            <div
              class="mt-2 sm:mt-8 flex justify-center align-middle content-center items-center gap-3"
            >
              <div class="w-full max-w-md">
                <SelectProduction />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import Module from '@components/module/Module.vue'
import PodloveButton from '@components/button/Button.vue'
import { PlusSmIcon, RefreshIcon } from '@heroicons/vue/outline'
import { selectors } from '@store'

import { injectStore, mapState } from 'redux-vuex'
import * as auphonic from '@store/auphonic.store'
import SelectProduction from '../components/SelectProduction.vue'
import SelectPreset from '../components/SelectPreset.vue'
import AuphonicLogo from '../components/Logo.vue'

export default defineComponent({
  components: {
    Module,
    PodloveButton,
    PlusSmIcon,
    RefreshIcon,
    SelectProduction,
    SelectPreset,
    AuphonicLogo,
  },

  setup() {
    return {
      state: mapState({
        preset: selectors.auphonic.preset,
        isInitializing: selectors.auphonic.isInitializing,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    createProduction() {
      this.dispatch(auphonic.createProduction())
    },
    createMultitrackProduction() {
      this.dispatch(auphonic.createMultitrackProduction())
    },
  },

  computed: {
    isInitializing() {
      return this.state.isInitializing
    },
    buttonState(): 'idle' | 'single' | 'multi' {
      if (!this.state.preset) {
        return 'idle'
      }

      return this.state.preset.is_multitrack ? 'multi' : 'single'
    },
  },
})
</script>
