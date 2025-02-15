<template>
  <div>
    <div class="flex flex-col gap-2">
      <!-- step one -->
      <div>
        <label class="block md:hidden text-sm font-medium text-gray-700">Upload Method</label>
        <select
          @change="handleServiceSelection"
          class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
        >
          <option
            v-for="service in services"
            :key="service.uuid"
            :value="service.uuid"
            :selected="service.uuid == currentServiceSelection"
          >
            {{ service.type }}: {{ service.display_name }}
          </option>
        </select>
      </div>

      <!-- step two -->
      <div v-if="currentService">
        <div v-if="currentService.type === 'file'">
          <label
            :for="file_key + 'file-upload'"
            class="relative flex items-center gap-2 cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none"
          >
            <div
              class="sm:mt-1 rounded-md bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-600 shadow-sm hover:bg-indigo-100"
            >
              {{ __('Choose File', 'podlove-podcasting-plugin-for-wordpress') }}
            </div>

            <div class="sm:mt-1 text-sm font-normal" v-if="filenameSelectedForUpload">
              {{ __('Selected for Upload:', 'podlove-podcasting-plugin-for-wordpress') }}
              <span>{{ filenameSelectedForUpload }}</span>
            </div>

            <input
              :id="file_key + 'file-upload'"
              name="file-upload"
              type="file"
              class="sr-only"
              @input="handleFileUploadSelection"
            />
          </label>
        </div>
        <div v-else-if="currentService.type === 'url'">
          <label
            :for="file_key + 'audio_source_url'"
            class="block text-sm font-medium text-gray-700"
            >File URL</label
          >
          <div class="mt-1">
            <input
              type="url"
              name="audio_source_url"
              :id="file_key + 'audio_source_url'"
              class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
              placeholder="https://example.com/audio.flac"
              :value="urlFieldValue"
              @input="handleUrlUpdate"
            />
          </div>
        </div>
        <div v-else>
          <div v-if="serviceFiles !== null">
            <label :for="file_key + 'external_file'" class="block text-sm font-medium text-gray-700"
              >File</label
            >
            <select
              name="audio_external_file"
              :id="file_key + 'external_file'"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              :value="externalFileFieldValue"
              @change="handleFileSelection"
            >
              <option v-for="file in serviceFiles" :key="file" :value="file" d>
                {{ file }}
              </option>
            </select>
          </div>
          <div v-else>...</div>
        </div>
        <div v-if="shouldSuggestSlug" class="mt-3">
          <button
            @click="() => setEpisodeSlug(slugCandidate)"
            type="button"
            class="relative text-xs inline-flex items-center rounded-md bg-white px-3 py-2 font-medium text-gray-500 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
          >
            <DocumentCheckIcon class="-ml-0.5 mr-1.5 h-4 w-4 text-gray-400" aria-hidden="true" />
            <span
              >{{ __('Use as Episode Slug:', 'podlove-podcasting-plugin-for-wordpress') }}
              <span class="font-mono">{{ slugCandidate }}</span></span
            >
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { selectors } from '@store'

import { injectStore, mapState } from 'redux-vuex'
import * as auphonic from '@store/auphonic.store'
import { Service } from '@store/auphonic.store'
import { update as updateEpisode } from '@store/episode.store'
import { disableSlugAutogen } from '@store/mediafiles.store'

import {
  Listbox,
  ListboxButton,
  ListboxLabel,
  ListboxOption,
  ListboxOptions,
} from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon as SelectorIcon } from '@heroicons/vue/24/solid'
import { get } from 'lodash'

import { DocumentCheckIcon } from '@heroicons/vue/24/outline'

export default defineComponent({
  props: ['file_key', 'track_index'],

  components: {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    CheckIcon,
    SelectorIcon,
    DocumentCheckIcon,
  },

  setup() {
    const state = mapState({
      services: selectors.auphonic.incomingServices,
      serviceFiles: selectors.auphonic.serviceFiles,
      fileSelections: selectors.auphonic.fileSelections,
      episodeSlug: selectors.episode.slug,
    })

    return {
      state,
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    set(prop: string, value: string | File | null) {
      this.dispatch(
        auphonic.updateFileSelection({
          key: this.file_key,
          prop,
          value,
        })
      )

      if (Number.isInteger(this.track_index)) {
        // mark track as modified
        this.dispatch(
          auphonic.updateTrack({
            track: {},
            index: this.track_index,
          })
        )
      }
    },
    handleFileSelection(event: Event): void {
      this.set('fileSelection', (event.target as HTMLSelectElement).value)
    },
    handleUrlUpdate(event: Event): void {
      this.set('urlValue', (event.target as HTMLInputElement).value)
    },
    handleFileUploadSelection(event: Event): void {
      const files = (event.target as HTMLInputElement).files
      if (files) {
        this.set('fileValue', files[0])
      } else {
        this.set('fileValue', null)
      }
    },
    handleServiceSelection(event: Event): void {
      this.set('currentServiceSelection', (event.target as HTMLSelectElement).value)
    },
    setEpisodeSlug(slug: String | null) {
      this.dispatch(updateEpisode({ prop: 'slug', value: slug }))
      this.dispatch(disableSlugAutogen())
    },
  },

  computed: {
    services(): Service[] {
      return this.state.services
    },
    currentService(): Service {
      return this.state.services.find((s: Service) => s.uuid === this.currentServiceSelection)
    },
    serviceFiles(): string[] | null {
      return get(this.state, ['serviceFiles', this.currentServiceSelection], null)
    },
    fileSelection(): any {
      return this.state.fileSelections[this.file_key]
    },
    filenameSelectedForUpload(): any {
      return this.fileSelection?.fileValue?.name
    },
    currentServiceSelection(): any {
      return this.fileSelection?.currentServiceSelection
    },
    urlFieldValue(): string {
      return this.fileSelection.urlValue
    },
    externalFileFieldValue(): string {
      return this.fileSelection.fileSelection
    },
    slugCandidate(): string | null {
      if (this.currentServiceSelection == 'url') {
        if (this.urlFieldValue) {
          return this.urlFieldValue.split('/').reverse()[0].split('.')[0]
        }
      }

      if (this.currentServiceSelection == 'file') {
        const filename = this.filenameSelectedForUpload
        return filename ? filename.split('.')[0] : null
      }

      return null
    },
    shouldSuggestSlug(): boolean {
      return this.slugCandidate != null && this.slugCandidate != this.state.episodeSlug
    },
  },
})
</script>
