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

        <div class="h-6"></div>

        <div v-if="isMultitrack">
          <h2 class="pb-4 text-base font-semibold">Audio Tracks</h2>

          <div class="bg-white shadow overflow-hidden rounded-md">
            <ul role="list" class="divide-y divide-gray-200">
              <li v-for="(track, index) in tracks" :key="`track-${index}`" class="px-6 py-4">
                <div class="flex flex-col sm:flex-row gap-3">
                  <label>
                    <span class="block text-sm font-medium text-gray-700">Track Identifier</span>
                    <input
                      type="text"
                      v-model="track.identifier"
                      class="mt-1 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                    />
                  </label>

                  <FileChooser
                    @update:modelValue="
                      (value) => {
                        updateTrack('fileSelection', value, index)
                      }
                    "
                  />
                </div>
              </li>
            </ul>
            <div class="bg-gray-50 px-4 py-4 sm:px-6">
              <podlove-button variant="primary" @click="addTrack">Add Track</podlove-button>
            </div>
          </div>
        </div>
        <div v-else class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
          <fieldset class="sm:col-span-4">
            <legend class="text-base font-medium text-gray-900">Audio Source</legend>
            <div class="mt-2 flex">
              <FileChooser @update:modelValue="(value) => (fileSelection = value)" />
            </div>
          </fieldset>
        </div>
      </div>
    </div>

    <div class="pt-5">
      <div class="flex justify-end gap-3">
        <podlove-button variant="secondary" @click="handleUpload"
          >Upload (debug only)</podlove-button
        >
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
import { Production, AudioTrack } from '@store/auphonic.store'

export default defineComponent({
  components: {
    PodloveButton,
    FileChooser,
  },

  data() {
    return {
      fileSelection: {},
    }
  },

  setup() {
    return {
      state: mapState({
        production: selectors.auphonic.production,
        tracks: selectors.auphonic.tracks,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    handleUpload() {
      this.dispatch(auphonic.uploadFile(this.fileSelection.value))
    },
    addTrack() {
      this.dispatch(auphonic.addTrack())
    },
    updateTrack(prop: string, value: any, index: number) {
      this.dispatch(
        auphonic.updateTrack({
          track: { [prop]: value },
          index,
        })
      )
    },
  },

  computed: {
    production(): Production {
      return this.state.production || {}
    },
    tracks(): AudioTrack[] {
      return this.state.tracks || []
    },
    isMultitrack(): boolean {
      return this.state.production && this.state.production.is_multitrack
    },
  },
})
</script>
