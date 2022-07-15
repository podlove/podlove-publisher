<template>
  <div>
    <div class="flex flex-col sm:flex-row gap-3">
      <!-- step one -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Upload Method</label>
        <select
          @change="set('currentServiceSelection', $event.target.value)"
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
            for="file-upload"
            class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
          >
            <div class="sm:mt-8">Upload a file</div>
            <input
              id="file-upload"
              name="file-upload"
              type="file"
              class="sr-only"
              @input="set('fileValue', $event.target.files[0])"
            />
          </label>
        </div>
        <div v-else-if="currentService.type === 'url'">
          <label for="audio_source_url" class="block text-sm font-medium text-gray-700"
            >File URL</label
          >
          <div class="mt-1">
            <input
              type="url"
              name="audio_source_url"
              id="audio_source_url"
              class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
              placeholder="https://example.com/audio.flac"
              @input="set('urlValue', $event.target.value)"
            />
          </div>
        </div>
        <div v-else>
          <div v-if="serviceFiles !== null">
            <label for="audio_external_file" class="block text-sm font-medium text-gray-700"
              >File</label
            >
            <select
              name="audio_external_file"
              id="audio_external_file"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              @change="set('fileSelection', $event.target.value)"
            >
              <option v-for="file in serviceFiles" :key="file" :value="file" d>
                {{ file }}
              </option>
            </select>
          </div>
          <div v-else>...</div>
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

import {
  Listbox,
  ListboxButton,
  ListboxLabel,
  ListboxOption,
  ListboxOptions,
} from '@headlessui/vue'
import { CheckIcon, SelectorIcon } from '@heroicons/vue/solid'

export default defineComponent({
  props: ['file_key'],

  components: {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    CheckIcon,
    SelectorIcon,
  },

  setup() {
    const state = mapState({
      services: selectors.auphonic.incomingServices,
      serviceFiles: selectors.auphonic.serviceFiles,
      fileSelections: selectors.auphonic.fileSelections,
    })

    return {
      state,
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    set(prop, value) {
      this.dispatch(
        auphonic.updateFileSelection({
          key: this.file_key,
          prop,
          value,
        })
      )
    },
  },

  computed: {
    services(): Service[] {
      return this.state.services
    },
    currentService(): Service {
      return this.state.services.find((s) => s.uuid === this.currentServiceSelection)
    },
    serviceFiles(): string[] | null {
      return this.currentServiceSelection && this.state.serviceFiles
        ? this.state.serviceFiles[this.currentServiceSelection]
        : null
    },
    fileSelection(): any {
      console.log(this.file_key, this.state.fileSelections[this.file_key])

      return this.state.fileSelections[this.file_key]
    },
    currentServiceSelection(): any {
      return this.fileSelection?.currentServiceSelection
    },
  },

  mounted() {
    console.log('m', this.currentServiceSelection, this.fileSelection)
    console.log('s', this.services)

    // TODO: restore values if present, otherwise set default below

    // FIXME: only if no value is set!
    // this.dispatch(
    //   auphonic.updateFileSelection({
    //     key: this.file_key,
    //     prop: 'currentServiceSelection',
    //     value: 'url',
    //   })
    // )
  },
})
</script>