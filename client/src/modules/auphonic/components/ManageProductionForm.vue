<template>
  <form class="pb-5 space-y-4">
    <div class="space-y-8">
      <div class="bg-white px-4 sm:px-6">
        <div class="-ml-4 -mt-4 flex flex-wrap items-center justify-between sm:flex-nowrap">
          <div class="ml-4 mt-4">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <AuphonicLogo className="mx-auto h-12 w-12 text-gray-400" />
              </div>
              <div class="ml-4">
                <h3 class="text-base font-semibold leading-6 text-gray-900">
                  {{ production?.metadata?.title }}
                </h3>
                <p class="text-sm text-gray-500">
                  {{ new Date(Date.parse(production?.creation_time)).toLocaleString() }}
                </p>
              </div>
            </div>
          </div>
          <div class="ml-4 mt-4 flex items-center space-x-4 text-xs">
            <span v-if="isSaving" class="inline-flex items-center animate-pulse text-green-600">
              <CloudIcon class="mr-1 h-4 w-4" aria-hidden="true" />
              {{ __('Saving', 'podlove-podcasting-plugin-for-wordpress') }}
            </span>
            <button
              v-if="production.status !== 3"
              @click="showImportPage = !showImportPage"
              type="button"
              class="relative inline-flex items-center rounded-md bg-white px-3 py-2 font-medium text-gray-500 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
            >
              <ArrowDownTrayIcon class="-ml-0.5 mr-1.5 h-4 w-4 text-gray-400" aria-hidden="true" />
              <span v-if="showImportPage">{{
                __('Hide Import', 'podlove-podcasting-plugin-for-wordpress')
              }}</span>
              <span v-if="!showImportPage">{{
                __('Show Import', 'podlove-podcasting-plugin-for-wordpress')
              }}</span>
            </button>
            <button
              @click="deselectProduction"
              type="button"
              class="relative inline-flex items-center rounded-md bg-white px-3 py-2 font-medium text-gray-500 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
            >
              <XIcon class="-ml-0.5 mr-1.5 h-4 w-4 text-gray-400" aria-hidden="true" />
              <span>{{
                __('Deselect Production', 'podlove-podcasting-plugin-for-wordpress')
              }}</span>
            </button>
          </div>
        </div>
      </div>
      <div class="space-y-4">
        <div v-if="showUploadScreen">
          {{ __('Uploading...', 'podlove-podcasting-plugin-for-wordpress') }}
        </div>

        <div v-if="showProcessingScreen">
          <div class="rounded-md bg-indigo-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <DatabaseIcon class="h-5 w-5 text-indigo-400" aria-hidden="true" />
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-indigo-800">{{ production.status_string }}</h3>
                <div class="mt-2 text-sm text-indigo-700">
                  <p>
                    {{
                      __(
                        'Auphonic is now processing your production. Please wait for it to finish.',
                        'podlove-podcasting-plugin-for-wordpress'
                      )
                    }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="production.status == 3 || showImportPage">
          <DonePage />
        </div>

        <div v-if="isMultitrack && showTrackEditor">
          <div class="bg-white shadow overflow-hidden rounded-md">
            <div class="px-6 pt-4 hidden md:block">
              <div class="md:grid md:grid-cols-3 md:gap-12">
                <div class="block text-sm font-medium text-gray-700 md:col-span-1">
                  {{ __('Track Identifier', 'podlove-podcasting-plugin-for-wordpress') }}
                </div>
                <div class="block text-sm font-medium text-gray-700 mt-5 md:mt-0 md:col-span-1">
                  {{ __('Upload Method', 'podlove-podcasting-plugin-for-wordpress') }}
                </div>
                <div class="block text-sm font-medium text-gray-700 mt-5 md:mt-0 md:col-span-1">
                  {{ __('Algorithm', 'podlove-podcasting-plugin-for-wordpress') }}
                </div>
                <div class="block w-8 md:col-span-1"></div>
              </div>
            </div>

            <ul role="list" class="divide-y divide-gray-200">
              <li v-for="(track, index) in tracks" :key="`xtrack-${index}`" class="px-6 py-4">
                <div class="md:grid md:grid-cols-3 md:gap-12">
                  <div class="md:col-span-1">
                    <label
                      :for="`track-id-${index}`"
                      class="block md:hidden text-sm font-medium text-gray-700"
                      >{{
                        __('Track Identifier', 'podlove-podcasting-plugin-for-wordpress')
                      }}</label
                    >
                    <input
                      :id="`track-id-${index}`"
                      type="text"
                      :value="track.identifier_new"
                      @input="handleUpdateIdentifier($event, index)"
                      class="mt-1 max-w-lg block w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:max-w-xs sm:text-sm border-gray-300 rounded-md"
                    />
                  </div>
                  <div class="mt-5 md:mt-0 md:col-span-1">
                    <div class="sm:items-baseline">
                      <div class="mt-4 sm:mt-0">
                        <FileChooser
                          :track_index="index"
                          :file_key="`${production.uuid}_t${index}`"
                        />
                        <div
                          v-if="track.input_file_name"
                          class="mt-2 px-3 py-2 bg-gray-100 rounded-md text-sm text-gray-700 break-all"
                        >
                          {{ __('Uploaded File:', 'podlove-podcasting-plugin-for-wordpress') }}
                          {{ track.input_file_name }}
                        </div>

                        <div v-if="uploadProgress(track.identifier) != null">
                          <div class="mt-2" aria-hidden="true">
                            <div class="overflow-hidden rounded-full bg-gray-100">
                              <div
                                class="h-2 rounded-full bg-indigo-600"
                                :style="{ width: uploadProgress(track.identifier) + '%' }"
                              ></div>
                            </div>
                            <div
                              class="mt-1 hidden grid-cols-4 text-sm font-medium text-gray-600 sm:grid"
                            >
                              <div>{{ uploadProgress(track.identifier) }}%</div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="md:col-span-1">
                    <div role="group" class="bg-gr" v-if="algorithmSettingsVisible(index)">
                      <div class="sm:items-baseline">
                        <div class="">
                          <div class="block md:hidden text-sm font-medium text-gray-700 py-2">
                            {{ __('Algorithm', 'podlove-podcasting-plugin-for-wordpress') }}
                          </div>
                          <div class="max-w-lg relative">
                            <div
                              :title="__('Remove Track', 'podlove-podcasting-plugin-for-wordpress')"
                              @click="removeTrack(track.identifier)"
                              class="absolute z-10 right-0 top-0 cursor-pointer text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                              <TrashIcon class="h-6 w-6" aria-hidden="true" />
                            </div>
                            <div class="space-y-4">
                              <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                  <input
                                    :id="`track_${index}_filtering`"
                                    type="checkbox"
                                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                    :checked="track.filtering"
                                    @input="handleToggleFiltering($event, index)"
                                  />
                                </div>
                                <div class="ml-3 text-sm">
                                  <label :for="`track_${index}_filtering`" class="text-gray-700">{{
                                    __('Filtering', 'podlove-podcasting-plugin-for-wordpress')
                                  }}</label>
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
                                      @input="handleToggleNoiseHum($event, index)"
                                    />
                                  </div>
                                  <div class="ml-3 text-sm">
                                    <label :for="`track_${index}_noisehum`" class="text-gray-700">{{
                                      __(
                                        'Noise and Hum Reduction',
                                        'podlove-podcasting-plugin-for-wordpress'
                                      )
                                    }}</label>
                                  </div>
                                </div>
                              </div>
                              <div>
                                <div
                                  class="relative flex justify-start align-middle items-center gap-3"
                                >
                                  <div class="text-sm">
                                    <label :for="`track_${index}_fgbg`" class="text-gray-700">{{
                                      __(
                                        'Fore/Background',
                                        'podlove-podcasting-plugin-for-wordpress'
                                      )
                                    }}</label>
                                  </div>

                                  <select
                                    :value="track.fore_background"
                                    @input="handleSelectForeBackground($event, index)"
                                    :id="`track_${index}_fgbg`"
                                    class="mt-1 block w-[168px] pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                  >
                                    <option value="auto">
                                      {{ __('Auto', 'podlove-podcasting-plugin-for-wordpress') }}
                                    </option>
                                    <option value="foreground">
                                      {{
                                        __(
                                          'Foreground Track',
                                          'podlove-podcasting-plugin-for-wordpress'
                                        )
                                      }}
                                    </option>
                                    <option value="background">
                                      {{
                                        __(
                                          'Background Track',
                                          'podlove-podcasting-plugin-for-wordpress'
                                        )
                                      }}
                                    </option>
                                    <option value="ducking">
                                      {{
                                        __(
                                          'Duck this Track',
                                          'podlove-podcasting-plugin-for-wordpress'
                                        )
                                      }}
                                    </option>
                                    <option value="unchanged">
                                      {{
                                        __(
                                          'Unchanged (Foreground)',
                                          'podlove-podcasting-plugin-for-wordpress'
                                        )
                                      }}
                                    </option>
                                  </select>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!--
                  <div class="w-8 md:col-span-1">
                    <span @click="removeTrack(track.identifier)">X</span>
                  </div>-->
                </div>
              </li>
            </ul>
            <div class="bg-gray-50 px-4 py-4 sm:px-6">
              <podlove-button variant="primary" @click="addTrack">{{
                __('Add Track', 'podlove-podcasting-plugin-for-wordpress')
              }}</podlove-button>
            </div>
          </div>
        </div>
        <div v-else-if="showTrackEditor" class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
          <fieldset class="sm:col-span-4">
            <legend class="text-base font-medium text-gray-900">
              {{ __('Audio Source', 'podlove-podcasting-plugin-for-wordpress') }}
            </legend>
            <div class="mt-2">
              <FileChooser :file_key="production.uuid" />

              <div
                v-if="production.input_file"
                class="mt-2 px-3 py-2 bg-gray-100 rounded-md text-sm text-gray-700 break-all"
              >
                <span>
                  {{ __('Uploaded File:', 'podlove-podcasting-plugin-for-wordpress') }}
                  {{ production.input_file }}
                </span>
              </div>

              <div v-if="uploadProgress('singletrack') != null">
                <div class="mt-2" aria-hidden="true">
                  <div class="overflow-hidden rounded-full bg-gray-100">
                    <div
                      class="h-2 rounded-full bg-indigo-600"
                      :style="{ width: uploadProgress('singletrack') + '%' }"
                    ></div>
                  </div>
                  <div class="mt-1 hidden grid-cols-4 text-sm font-medium text-gray-600 sm:grid">
                    <div>{{ uploadProgress('singletrack') }}%</div>
                  </div>
                </div>
              </div>
            </div>
          </fieldset>
        </div>
      </div>
    </div>

    <div class="pt-5">
      <div class="flex flex-col sm:flex-row gap-4 sm:gap-2 justify-between">
        <WebhookToggle />
        <div class="flex justify-end gap-3">
          <a
            :href="production?.edit_page"
            target="_blank"
            class="inline-flex items-center rounded border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-500 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >{{ __('Edit in Auphonic', 'podlove-podcasting-plugin-for-wordpress') }}
            <ExternalLinkIcon class="ml-1 -mr-0.5 h-4 w-4" aria-hidden="true"
          /></a>
          <podlove-button
            :variant="isSaving ? 'secondary-disabled' : 'secondary'"
            @click="saveProduction"
            >{{ __('Save Production', 'podlove-podcasting-plugin-for-wordpress') }}</podlove-button
          >
          <podlove-button variant="primary" @click="startProduction">{{
            __('Start Production', 'podlove-podcasting-plugin-for-wordpress')
          }}</podlove-button>
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
import { Production, AudioTrack } from '@store/auphonic.store'

import DonePage from './production_form/DonePage.vue'
import WebhookToggle from './WebhookToggle.vue'

import AuphonicLogo from '../components/Logo.vue'

import {
  XMarkIcon as XIcon,
  CogIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  CircleStackIcon as DatabaseIcon,
  ArrowTopRightOnSquareIcon as ExternalLinkIcon,
  CloudIcon,
  TrashIcon,
  ArrowDownIcon,
  ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'
import { get } from 'lodash'

type AlgorithmType = { [key in number]?: any }

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
    TrashIcon,
    ArrowDownIcon,
    ArrowDownTrayIcon,
    AuphonicLogo,
    DonePage,
    WebhookToggle,
  },

  data() {
    return {
      algorithmSettings: {} as AlgorithmType,
      showImportPage: false,
    }
  },

  setup() {
    return {
      state: mapState({
        production: selectors.auphonic.production,
        tracks: selectors.auphonic.tracks,
        isSaving: selectors.auphonic.isSaving,
        progress: (state) => state.progress,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    saveProduction() {
      this.dispatch(
        auphonic.saveProduction({
          uuid: this.production.uuid,
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
    removeTrack(id: string) {
      this.dispatch(auphonic.removeTrack(id))
    },
    updateTrack(prop: string, value: any, index: number) {
      this.dispatch(
        auphonic.updateTrack({
          track: { [prop]: value },
          index,
        })
      )
    },
    algorithmSettingsVisible(index: number): boolean {
      // TODO: add UI to toggle all algorithm settings at once
      return true
      // return this.algorithmSettings[index] || false
    },
    toggleAlgorithmSettingVisible(index: number): void {
      this.algorithmSettings[index] = !get(this.algorithmSettings, index, false)
    },
    handleSelectForeBackground(event: Event, index: number): void {
      this.updateTrack('fore_background', (event.target as HTMLSelectElement).value, index)
    },
    handleToggleNoiseHum(event: Event, index: number): void {
      this.updateTrack('noise_and_hum_reduction', (event.target as HTMLInputElement).checked, index)
    },
    handleToggleFiltering(event: Event, index: number): void {
      this.updateTrack('filtering', (event.target as HTMLInputElement).checked, index)
    },
    handleUpdateIdentifier(event: Event, index: number): void {
      this.updateTrack('identifier_new', (event.target as HTMLInputElement).value, index)
    },
    uploadProgress(key: string): string | null {
      return this.state.progress[key] || null
    },
  },

  computed: {
    production(): Production {
      return this.state.production || {}
    },
    isSaving(): boolean {
      return this.state.isSaving
    },
    showProcessingScreen(): boolean {
      return [1, 4, 5, 6, 7, 8, 12, 13, 14].includes(this.production.status)
    },
    showUploadScreen(): boolean {
      return this.production.status === 0
    },
    showTrackEditor(): boolean {
      return [9, 10, 11].includes(this.production.status)
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
