<template>
  <form class="space-y-8 divide-y divide-gray-200">
    <div class="space-y-8 divide-y divide-gray-200">
      <div>
        <div class="flex justify-between items-start">
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">Manage Production</h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ production?.metadata?.title }}
            </p>
          </div>
          <div class="mt-1 flex items-center space-x-4">
            <span v-if="isSaving" class="inline-flex items-center animate-pulse text-green-600">
              <CloudIcon class="mr-1 h-4 w-4" aria-hidden="true" />
              Saving
            </span>
            <a
              :href="production?.edit_page"
              target="_blank"
              class="inline-flex items-center rounded border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-500 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
              >Edit in Auphonic <ExternalLinkIcon class="ml-1 -mr-0.5 h-4 w-4" aria-hidden="true"
            /></a>

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

        <div v-if="showUploadScreen">Uploading...</div>

        <div v-if="showProcessingScreen">
          <div class="rounded-md bg-indigo-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <DatabaseIcon class="h-5 w-5 text-indigo-400" aria-hidden="true" />
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-indigo-800">{{ production.status_string }}</h3>
                <div class="mt-2 text-sm text-indigo-700">
                  <p>Auphonic is now processing your production. Please wait for it to finish.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="production.status == 3">
          <DonePage />
        </div>

        <div v-if="isMultitrack && showTrackEditor">
          <h2 class="pb-4 text-base font-semibold">Audio Tracks</h2>

          <div class="bg-white shadow overflow-hidden rounded-md max-w-3xl">
            <ul role="list" class="divide-y divide-gray-200">
              <li v-for="(track, index) in tracks" :key="`xtrack-${index}`" class="px-6 py-4">
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
                        :value="track.identifier_new"
                        @input="updateTrack('identifier_new', $event.target.value, index)"
                        class="mt-1 max-w-lg block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:max-w-xs sm:text-sm border-gray-300 rounded-md"
                      />
                    </div>
                    <div class="mt-5 md:mt-0 md:col-span-2">
                      <div class="sm:items-baseline">
                        <div class="mt-4 sm:mt-0">
                          <div class="flex flex-col sm:flex-row gap-3">
                            <FileChooser
                              :track_index="index"
                              :file_key="`${production.uuid}_t${index}`"
                            />
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
        <div v-else-if="showTrackEditor" class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
          <fieldset class="sm:col-span-4">
            <legend class="text-base font-medium text-gray-900">Audio Source</legend>
            <div class="mt-2">
              <div class="flex flex-col sm:flex-row gap-3">
                <FileChooser :file_key="production.uuid" />
              </div>
              <div v-if="production.input_file" class="mt-1 text-sm text-gray-700">
                Current File: {{ production.input_file }}
              </div>
            </div>
          </fieldset>
        </div>
      </div>
    </div>

    <div class="pt-5">
      <div class="flex justify-between">
        <WebhookToggle />
        <div class="flex justify-end gap-3">
          <podlove-button variant="secondary" @click="saveProduction"
            >Save Production</podlove-button
          >
          <podlove-button variant="primary" @click="startProduction"
            >Start Production</podlove-button
          >
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
import Timestamp from '@lib/timestamp'
import DonePage from './production_form/DonePage.vue'
import WebhookToggle from './WebhookToggle.vue'

import {
  XIcon,
  CogIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  DatabaseIcon,
  ExternalLinkIcon,
  CloudIcon,
} from '@heroicons/vue/outline'

export default defineComponent({
  components: {
    PodloveButton,
    FileChooser,
    CogIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    XIcon,
    DatabaseIcon,
    ExternalLinkIcon,
    CloudIcon,
    DonePage,
    WebhookToggle,
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
        isSaving: selectors.auphonic.isSaving,
        episode_title: selectors.episode.title,
        episode_subtitle: selectors.episode.subtitle,
        episode_summary: selectors.episode.summary,
        episode_number: selectors.episode.number,
        episode_poster: selectors.episode.poster || selectors.podcast.poster,
        podcast_title: selectors.podcast.title,
        podcast_author: selectors.podcast.author,
        podcast_link: selectors.podcast.link,
        chapters: selectors.chapters.list,
        baseUrl: selectors.runtime.baseUrl,
        postId: selectors.post.id,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    saveProduction() {
      this.dispatch(
        auphonic.saveProduction({
          uuid: this.production.uuid,
          productionPayload: this.payload,
          tracksPayload: this.tracksPayload,
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
    isSaving(): boolean {
      return this.state.isSaving
    },
    showProcessingScreen() {
      return [1, 4, 5, 6, 7, 8, 12, 13, 14].includes(this.production.status)
    },
    showUploadScreen() {
      return this.production.status === 0
    },
    showTrackEditor() {
      return [9, 10, 11].includes(this.production.status)
    },
    tracks(): AudioTrack[] {
      return this.state.tracks || []
    },
    isMultitrack(): boolean {
      return this.state.production && this.state.production.is_multitrack
    },
    isLocalDevelopment(): boolean {
      // @ts-ignore
      return import.meta.env.MODE == 'development'
    },
    productionPayload(): object {
      let payload = this.state.productionPayload

      // remove output_files from payload, because it doubles them
      const { output_files, ...newPayload } = payload

      return {
        ...newPayload,
        // TODO: rewrite image logic to use file upload instead of url, then we
        // do not need this isLocalDevelopment switch any more
        image: this.isLocalDevelopment ? '' : this.state.episode_poster,
        metadata: {
          ...newPayload.metadata,
          title: this.state.episode_title,
          subtitle: this.state.episode_subtitle,
          summary: this.state.episode_summary,
          artist: this.state.podcast_author,
          album: this.state.podcast_title,
          url: this.state.podcast_link,
          track: this.state.episode_number,
        },
        chapters: this.state.chapters.map((chapter) => {
          return {
            title: chapter.title,
            url: chapter.href,
            start: new Timestamp(chapter.start).pretty,
          }
        }),
      }
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
    tracksPayload() {
      if (!this.isMultitrack) {
        return []
      }

      return this.tracks
        .map((track, index) => {
          const state = track.save_state

          if (state == 'unchanged') {
            return {}
          }

          let upload = {}

          // FIXME: currently service is always url when selecting an existing production
          let fileReference = {}
          if (this.fileSelections[index].service == 'url') {
            fileReference = {
              input_file: this.fileSelections[index].value,
            }
          } else if (this.fileSelections[index].service == 'file') {
            upload = {
              track_id: track.identifier_new,
              file: this.fileSelections[index].value,
            }
          } else {
            fileReference = {
              service: this.fileSelections[index].service,
              input_file: this.fileSelections[index].value,
            }
          }

          return {
            state,
            upload,
            payload: {
              type: 'multitrack',
              id: track.identifier,
              id_new: track.identifier_new,
              ...fileReference,
              algorithms: {
                denoise: track.noise_and_hum_reduction,
                hipfilter: track.filtering,
                backforeground: track.fore_background,
              },
            },
          }
        })
        .filter((t) => Object.keys(t).length > 0)
    },
    payload(): object {
      let fileReference = {}
      let upload = {}

      // for single track, add file selection to payload
      if (!this.isMultitrack) {
        if (this.fileSelections.service == 'url') {
          fileReference = {
            input_file: this.fileSelections.value,
          }
        } else if (this.fileSelections.service == 'file') {
          fileReference = {
            input_file: this.fileSelections.value,
          }
        } else {
          fileReference = {
            service: this.fileSelections.service,
            input_file: this.fileSelections.value,
          }
        }
      }

      return {
        ...this.productionPayload,
        ...fileReference,
      }
    },
  },
})
</script>
