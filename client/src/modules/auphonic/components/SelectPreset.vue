<template>
  <Listbox as="div" @update:modelValue="setPreset" :value="currentPreset">
    <ListboxLabel class="block text-sm font-medium text-gray-600 sr-only">
      {{ __('Select Preset') }}
    </ListboxLabel>
    <div class="mt-1 relative">
      <ListboxButton
        class="relative w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
      >
        <span class="w-full inline-flex truncate">
          <span v-if="currentPreset" class="truncate">{{ currentPreset._select.name }}</span>
          <span v-else class="truncate">{{ __('Select Preset') }}</span>
        </span>
        <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
          <SelectorIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
        </span>
      </ListboxButton>

      <transition
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <ListboxOptions
          class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-36 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
        >
          <ListboxOption
            as="template"
            v-for="preset in presets"
            :key="preset._select.date"
            :value="preset"
            v-slot="{ active }"
          >
            <li
              :class="[
                active ? 'text-white bg-indigo-600' : 'text-gray-900',
                'cursor-default select-none relative py-2 pl-3 pr-9',
              ]"
            >
              <div class="flex">
                <span :class="['font-normal', 'truncate']">
                  {{ preset._select.name }}
                </span>
                <span
                  v-if="preset._select.is_multitrack"
                  :class="[active ? 'text-indigo-200' : 'text-gray-500', 'ml-2 truncate']"
                >
                  {{ __('Multitrack') }}
                </span>
              </div>
            </li>
          </ListboxOption>
        </ListboxOptions>
      </transition>
    </div>
  </Listbox>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue'
import { selectors } from '@store'

import { injectStore, mapState } from 'redux-vuex'

import * as auphonic from '@store/auphonic.store'

import {
  Listbox,
  ListboxButton,
  ListboxLabel,
  ListboxOption,
  ListboxOptions,
} from '@headlessui/vue'
import { CheckIcon, SelectorIcon } from '@heroicons/vue/solid'
import { Preset } from '@store/auphonic.store'

type PresetWithSelectionData = Preset & {
  _select: {
    name: string
    date: string
    is_multitrack: boolean
  }
}

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

  setup() {
    return {
      state: mapState({
        presets: selectors.auphonic.presets,
        currentPreset: selectors.auphonic.preset,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    setPreset(preset: Preset) {
      this.dispatch(auphonic.setPreset(preset))
    },
  },

  computed: {
    presets(): PresetWithSelectionData[] {
      return this.state.presets.map((preset: Preset) => {
        const date = preset.creation_time.split('T')[0]
        const name = preset.preset_name
        const is_multitrack = preset.is_multitrack

        return { ...preset, _select: { name, date, is_multitrack } }
      })
    },
    currentPreset(): PresetWithSelectionData | null {
      return this.state.currentPreset
    },
  },
})
</script>
