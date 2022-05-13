<template>
  <form class="space-y-8 divide-y divide-gray-200">
    <div class="space-y-8 divide-y divide-gray-200">
      <div>
        <div class="flex justify-between items-start">
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">Manage Production</h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ production?.metadata?.title }}
              <br class="block sm:hidden" />
              <a
                :href="production?.edit_page"
                target="_blank"
                class="cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
                >edit in Auphonic</a
              >
              <br />
              Status: {{ production.status_string }}
            </p>
          </div>
          <div class="mt-1">
            <button
              type="button"
              class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              @click="deselectProduction"
            >
              <span class="sr-only">Change Production</span>
              <XIcon class="h-6 w-6" aria-hidden="true" />
            </button>
          </div>
        </div>

        <div class="h-6"></div>

        <div v-if="isMultitrack">
          <h2 class="pb-4 text-base font-semibold">Audio Tracks</h2>

          <div class="bg-white shadow overflow-hidden rounded-md max-w-3xl">
            <ul role="list" class="divide-y divide-gray-200">
              <li
                v-for="(track, index) in tracks"
                :key="`xtrack-${index}`"
                class="px-6 py-4 divide-y divide-gray-200 space-y-4"
              >
                <div class="md:flex md:gap-4">
                  <div class="md:grid md:grid-cols-3 md:gap-12">
                    <div class="md:col-span-1">
                      <label
                        :for="`track-id-${index}`"
                        class="block text-sm font-medium text-gray-700"
                        >Track Identifier</label
                      >
                      <input
                        :id="`track-id-${index}`"
                        type="text"
                        :value="track.identifier"
                        @input="updateTrack('identifier', $event.target.value, index)"
                        class="mt-1 max-w-lg block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:max-w-xs sm:text-sm border-gray-300 rounded-md"
                      />
                    </div>
                    <div class="mt-5 md:mt-0 md:col-span-2">
                      <div class="sm:items-baseline">
                        <div class="mt-4 sm:mt-0">
                          <div class="flex flex-col sm:flex-row gap-3">
                            <FileChooser :file_key="`${production.uuid}_t${index}`" />
                          </div>
                          <div v-if="track.input_file_name" class="mt-1 text-sm text-gray-700">
                            Current File: {{ track.input_file_name }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="mt-5 md:mt-1">
                    <div title="Algorithm Settings" @click="toggleAlgorithmSettingVisible(index)">
                      <span
                        v-if="algorithmSettingsVisible(index)"
                        class="flex sm:mt-[26px] gap-1 items-center text-sm text-gray-700 cursor-pointer"
                      >
                        <ChevronRightIcon class="h-6 w-6 text-gray-500" />
                        <span class="block md:hidden">Hide Algorithm Settings</span>
                      </span>
                      <span
                        v-else
                        class="flex sm:mt-[26px] gap-1 items-center text-sm text-gray-700 cursor-pointer"
                      >
                        <ChevronDownIcon class="h-6 w-6 text-gray-500" />
                        <span class="block md:hidden">Show Algorithm Settings</span>
                      </span>
                    </div>
                  </div>
                </div>
                <div role="group" class="pt-6 bg-gr" v-show="algorithmSettingsVisible(index)">
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
        <podlove-button variant="secondary" @click="saveProduction">Save Production</podlove-button>
        <podlove-button variant="primary" @click="startProduction">Start Production</podlove-button>
      </div>
    </div>

    <div class="pt-5">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">Production Payload Preview</div>
        <div class="bg-gray-50 px-4 py-5 sm:p-6 font-mono whitespace-pre">
          {{ JSON.stringify(payload, null, '\t') }}
        </div>
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

import { XIcon, CogIcon, ChevronDownIcon, ChevronRightIcon } from '@heroicons/vue/outline'

export default defineComponent({
  components: {
    PodloveButton,
    FileChooser,
    CogIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    XIcon,
  },

  data() {
    return {
      algorithmSettings: {},
    }
  },

  setup() {
    return {
      state: mapState({
        production: selectors.auphonic.production,
        tracks: selectors.auphonic.tracks,
        fileSelections: selectors.auphonic.fileSelections,
        productionPayload: selectors.auphonic.productionPayload,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    saveProduction() {
      this.dispatch(
        auphonic.saveProduction({
          uuid: this.production.uuid,
          payload: this.payload,
        })
      )
    },
    startProduction() {
      this.dispatch(auphonic.startProduction({ uuid: this.production.uuid }))
    },
    deselectProduction() {
      this.dispatch(auphonic.deselectProduction())
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
    algorithmSettingsVisible(index): boolean {
      return this.algorithmSettings[index] || false
    },
    toggleAlgorithmSettingVisible(index): void {
      this.algorithmSettings[index] = this.algorithmSettings.hasOwnProperty(index)
        ? !this.algorithmSettings[index]
        : true
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
    productionPayload(): object {
      return this.state.productionPayload
    },
    fileSelections(): any {
      const prepareFile = (selection: FileSelection) => {
        if (!selection) {
          return {}
        }

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
    payload(): object {
      const payload = {
        reset_data: true,
        ...this.productionPayload,
        // FIXME: only override type=multitrack files, keep intro/outro files intact
        multi_input_files: this.tracks.map((track, index) => {
          let fileReference = {}

          if (this.fileSelections[index].service == 'url') {
            fileReference = {
              input_file: this.fileSelections[index].value,
            }
          } else if (this.fileSelections[index].service == 'file') {
            // is uploaded separately
          } else {
            fileReference = {
              service: this.fileSelections[index].service,
              input_file: this.fileSelections[index].value,
            }
          }

          return {
            type: 'multitrack',
            id: track.identifier,
            ...fileReference,
            algorithms: {
              denoise: track.noise_and_hum_reduction,
              hipfilter: track.filtering,
              backforeground: track.fore_background,
            },
          }
        }),
      }

      return payload
    },
  },
})

// TODO
// - think about save/upload/start flow
//   - there should not be an upload button
//   - is it possible there is no "save" button, only "start"?
//   - if there is a save button, it must be impossible to "forget" to save before starting (for example by autosave before start)
// - maybe/somehow: connect track id to contributor
// - hide audio tracks when status is related to "processing/done"?
// - show spinners & deactivate buttons while waiting for blocking API responses like starting the production
// - disable "start production" when status is "incomplete"?
// FIXME
// - when there is a "current file" restored from auphonic, the file selector must reflect that value (and, more importantly, a save must not override the file selection)
// NEXT
// - display status
//   - query only status: https://auphonic.com/api/production/{uuid}/status.json
//   - all status codes: https://auphonic.com/api/info/production_status.json
// - when status is something indicating it's "processing", poll for changes
// - how do I get the episode title (and other episode metadata)? I can fetch it via API but that may not be up to date?

// === STATUS CODES ===
// {
//     "0": "File Upload",
//     "1": "Waiting",
//     "2": "Error",
//     "3": "Done",
//     "4": "Audio Processing",
//     "5": "Audio Encoding",
//     "6": "Outgoing File Transfer",
//     "7": "Audio Mono Mixdown",
//     "8": "Split Audio On Chapter Marks",
//     "9": "Incomplete",
//     "10": "Production Not Started Yet",
//     "11": "Production Outdated",
//     "12": "Incoming File Transfer",
//     "13": "Stopping the Production",
//     "14": "Speech Recognition"
// }
</script>
