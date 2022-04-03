<template>
  <div>
    <label for="location" class="block text-sm font-medium text-gray-700">Upload Method</label>

    <!-- step one -->
    <select
      v-model="currentServiceSelection"
      class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
    >
      <option v-for="service in services" :key="service.uuid" :value="service.uuid">
        {{ service.type }}: {{ service.display_name }}
      </option>
    </select>

    <!-- step two -->
    <div v-if="currentService" class="mt-1 py-2">
      <div v-if="currentService.type === 'file'">
        <label
          for="file-upload"
          class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
        >
          <span>Upload a file</span>
          <input id="file-upload" name="file-upload" type="file" class="sr-only" />
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
          />
        </div>
      </div>
      <div v-else>
        <div v-if="serviceFiles !== null">
          <label for="audio_external_file" class="block text-sm font-medium text-gray-700"
            >File</label
          >
          <select
            v-model="fileSelection"
            name="audio_external_file"
            id="audio_external_file"
            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
          >
            <option v-for="file in serviceFiles" :key="file" :value="file">
              {{ file }}
            </option>
          </select>
        </div>
        <div v-else>...</div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { ref, defineComponent } from 'vue'
import Module from '@components/module/Module.vue'
import PodloveButton from '@components/button/Button.vue'
import { PlusSmIcon } from '@heroicons/vue/outline'
import { selectors } from '@store'

import { injectStore, mapState } from 'redux-vuex'
import * as auphonic from '@store/auphonic.store'
import { Service } from '@store/auphonic.store'
import SelectProduction from './components/SelectProduction.vue'
import ManageProductionForm from './components/ManageProductionForm.vue'
import AuphonicLogo from './components/Logo.vue'

import {
  Listbox,
  ListboxButton,
  ListboxLabel,
  ListboxOption,
  ListboxOptions,
} from '@headlessui/vue'
import { CheckIcon, SelectorIcon } from '@heroicons/vue/solid'

export default defineComponent({
  components: {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    CheckIcon,
    SelectorIcon,
  },

  data() {
    return {
      currentServiceSelection: null,
      fileSelection: null,
    }
  },

  setup() {
    const state = mapState({
      services: selectors.auphonic.incomingServices,
      serviceFiles: selectors.auphonic.serviceFiles,
    })

    return {
      state,
      dispatch: injectStore().dispatch,
    }
  },

  watch: {
    ready() {
      this.currentServiceSelection = this.state.services[0]?.uuid
    },
    currentServiceSelection(uuid) {
      this.dispatch(auphonic.selectService(uuid))
    },
  },

  computed: {
    ready(): boolean {
      return this.state.services.length > 0
    },
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
  },
})
</script>
