<template>
  <Listbox as="div" @update:modelValue="setProduction" :value="currentProduction">
    <ListboxLabel class="block text-sm font-medium text-gray-600 sr-only">
      {{ __('Select Existing Production', 'podlove-podcasting-plugin-for-wordpress') }}
    </ListboxLabel>
    <div class="mt-1 relative">
      <ListboxButton
        class="relative w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
      >
        <span class="w-full inline-flex truncate">
          <span v-if="currentProduction" class="truncate">{{
            currentProduction.metadata.title
          }}</span>
          <span v-else class="truncate">&nbsp;</span>
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
            v-for="production in productions"
            :key="production._select.date"
            :value="production"
            v-slot="{ active }"
          >
            <li
              :class="[
                active ? 'text-white bg-indigo-600' : 'text-gray-900',
                'cursor-default select-none relative py-2 pl-3 pr-9',
              ]"
            >
              <div class="flex justify-between">
                <span :class="['font-normal', 'truncate']">
                  <span :class="[active ? 'text-indigo-200' : 'text-gray-500', 'ml-2 truncate']">
                    {{ production._select.date }}
                  </span>
                  {{ production._select.name }}
                </span>
                <span
                  :class="[
                    active ? 'text-indigo-200' : 'text-gray-500',
                    'ml-2 truncate flex-shrink-0',
                  ]"
                >
                  {{ production.status_string }}
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
import { defineComponent } from 'vue'
import { selectors } from '@store'

import { injectStore, mapState } from 'redux-vuex'

import * as auphonic from '@store/auphonic.store'
import { Production } from '@store/auphonic.store'

import {
  Listbox,
  ListboxButton,
  ListboxLabel,
  ListboxOption,
  ListboxOptions,
} from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon as SelectorIcon } from '@heroicons/vue/24/solid'

type ProductionWithSelectionData = Production & {
  _select: {
    name: string
    date: string
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
        productions: selectors.auphonic.productions,
        production: selectors.auphonic.production,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    setProduction(production: Production) {
      this.dispatch(auphonic.setProduction(production))
    },
  },

  computed: {
    productions(): ProductionWithSelectionData[] {
      return this.state.productions.map((production: Production) => {
        const date = production.creation_time.split('T')[0]
        const name = production.metadata.title

        return { ...production, _select: { name, date } }
      })
    },
    currentProduction(): Production | null {
      return this.state.production
    },
  },
})
</script>
