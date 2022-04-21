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
                <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:pt-5">
                  <label
                    for="first-name"
                    class="text-base font-medium text-gray-900 sm:text-sm sm:text-gray-700"
                  >
                    Track Identifier
                  </label>
                  <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input
                      type="text"
                      :value="track.identifier"
                      @input="updateTrack('identifier', $event.target.value, index)"
                      class="max-w-lg block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:max-w-xs sm:text-sm border-gray-300 rounded-md"
                    />
                  </div>
                </div>

                <div role="group" class="pt-6">
                  <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-baseline">
                    <div>
                      <div class="text-base font-medium text-gray-900 sm:text-sm sm:text-gray-700">
                        File
                      </div>
                    </div>
                    <div class="mt-4 sm:mt-0 sm:col-span-2">
                      <div class="flex flex-col sm:flex-row gap-3">
                        <FileChooser :file_key="`${production.uuid}_t${index}`" />
                      </div>
                    </div>
                  </div>
                </div>
                <div role="group" class="pt-6">
                  <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-baseline">
                    <div>
                      <div class="text-base font-medium text-gray-900 sm:text-sm sm:text-gray-700">
                        Algorithm
                      </div>
                    </div>
                    <div class="mt-4 sm:mt-0 sm:col-span-2">
                      <div class="max-w-lg space-y-4">
                        <div class="relative flex items-start">
                          <div class="flex items-center h-5">
                            <input
                              :id="`track_${index}_filtering`"
                              type="checkbox"
                              class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                              :checked="track.filtering"
                              @input="updateTrack('filtering', $event.target.checked, index)"
                            />
                          </div>
                          <div class="ml-3 text-sm">
                            <label
                              :for="`track_${index}_filtering`"
                              class="font-medium text-gray-700"
                              >Filtering</label
                            >
                          </div>
                        </div>
                        <div>
                          <div class="relative flex items-start">
                            <div class="flex items-center h-5">
                              <input
                                :id="`track_${index}_noisehum`"
                                type="checkbox"
                                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                :checked="track.noise_and_hum_reduction"
                                @input="
                                  updateTrack(
                                    'noise_and_hum_reduction',
                                    $event.target.checked,
                                    index
                                  )
                                "
                              />
                            </div>
                            <div class="ml-3 text-sm">
                              <label
                                :for="`track_${index}_noisehum`"
                                class="font-medium text-gray-700"
                                >Noise and Hum Reduction</label
                              >
                            </div>
                          </div>
                        </div>
                        <div>
                          <div class="relative flex justify-start align-middle items-center gap-3">
                            <div class="text-sm">
                              <label :for="`track_${index}_fgbg`" class="font-medium text-gray-700"
                                >Fore/Background</label
                              >
                            </div>

                            <select
                              :value="track.fore_background"
                              @input="updateTrack('fore_background', $event.target.value, index)"
                              :id="`track_${index}_fgbg`"
                              class="mt-1 block w-[168px] pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                            >
                              <option value="auto">Auto</option>
                              <option value="foreground">Foreground Track</option>
                              <option value="background">Background Track</option>
                              <option value="ducking">Duck this Track</option>
                              <option value="unchanged">Unchanged (Foreground)</option>
                            </select>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
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
              <FileChooser :file_key="production.uuid" />
            </div>
          </fieldset>
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
import { Production, AudioTrack, FileSelection } from '@store/auphonic.store'


export default defineComponent({
  components: {
    PodloveButton,
    FileChooser,
  },

  data() {
    return {}
  },

  setup() {
    return {
      state: mapState({
        production: selectors.auphonic.production,
        tracks: selectors.auphonic.tracks,
        fileSelections: selectors.auphonic.fileSelections,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
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
    fileSelections(): any {
      const prepareFile = (selection: FileSelection) => {
        switch (selection.currentServiceSelection) {
          case 'url':
            return { service: 'url', value: selection.urlValue }
          case 'file':
            return { service: 'file', value: selection.fileValue }
          default:
            return { service: selection.currentServiceSelection, value: selection.fileSelection }
        }
      }

      return this.isMultitrack
        ? this.tracks.reduce((agg, _track, index) => {
            agg.push(prepareFile(this.state.fileSelections[`${this.production.uuid}_t${index}`]))
            return agg
          }, [])
        : prepareFile(this.state.fileSelections[this.production.uuid])
    },
  },
})
</script>
