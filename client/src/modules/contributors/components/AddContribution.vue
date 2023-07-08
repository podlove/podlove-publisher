<template>
  <div class="block hover:bg-gray-50">
    <div class="flex items-center px-4 py-4 sm:px-6">
      <div class="flex min-w-0 flex-1 items-center">
        <div class="min-w-0 flex-1 px-4 md:grid md:grid-cols-2 md:gap-4">
          <Combobox as="div" v-model="data">
            <ComboboxLabel class="block text-sm font-medium leading-6 text-gray-900">{{
              __('Select Contributor')
            }}</ComboboxLabel>
            <div class="relative mt-2 max-w-sm">
              <ComboboxInput
                ref="trigger"
                class="w-full rounded-md border-0 bg-white py-1.5 pl-3 pr-12 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                @change="filterContributors($event)"
                :displayValue="() => query"
              />
              <ComboboxButton
                class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-none"
              >
                <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
              </ComboboxButton>
            </div>
            <div ref="container">
              <ComboboxOptions
                v-if="filteredContributors.length > 0"
                class="absolute max-w-sm z-50 mt-1 max-h-56 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
              >
                <ComboboxOption
                  v-for="contributor in filteredContributors"
                  :key="contributor.id || 'create'"
                  :value="contributor"
                  as="template"
                  v-slot="{ active, selected }"
                >
                  <li
                    :class="[
                      'relative cursor-default select-none py-2 pl-3 pr-9',
                      active ? 'bg-indigo-600 text-white' : 'text-gray-900',
                    ]"
                  >
                    <div class="flex items-center">
                      <img
                        v-if="contributor.avatar"
                        :src="contributor.avatar"
                        alt=""
                        class="h-6 w-6 flex-shrink-0 rounded-full"
                        @error="contributor.avatar = ''"
                      />
                      <UserCircleIcon
                        v-if="!contributor.avatar"
                        class="h-6 w-6 flex-shrink-0 rounded-full text-gray-500"
                      />
                      <span :class="['ml-3 truncate', selected && 'font-semibold']">
                        {{ contributor.realname }}
                      </span>
                    </div>

                    <span
                      v-if="selected"
                      :class="[
                        'absolute inset-y-0 right-0 flex items-center pr-4',
                        active ? 'text-white' : 'text-indigo-600',
                      ]"
                    >
                      <CheckIcon class="h-5 w-5" aria-hidden="true" />
                    </span>
                  </li>
                </ComboboxOption>
              </ComboboxOptions>
            </div>
          </Combobox>
        </div>
      </div>
      <div class="flex space-x-2 justify-end">
        <button class="text-red-600" @click="close()">
          <x-icon class="w-5 h-5" />
        </button>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { get } from 'lodash'

import { XIcon, CheckIcon, ChevronDownIcon, UserCircleIcon } from '@heroicons/vue/outline'

import {
  Combobox,
  ComboboxButton,
  ComboboxInput,
  ComboboxLabel,
  ComboboxOption,
  ComboboxOptions,
} from '@headlessui/vue'

import { injectStore, mapState } from 'redux-vuex'

import { selectors } from '@store'
import { PodloveContributor } from '../../../types/contributors.types'
import { PodloveEpisodeContribution } from '../../../types/episode.types'
import { usePopper } from '@lib/popper'

export default defineComponent({
  data() {
    return {
      query: '',
      data: '',
    }
  },
  emits: ['addContributor', 'createContributor', 'close'],
  setup(): {
    dispatch: Function
    state: {
      contributors: PodloveContributor[]
      episodeContributions: PodloveEpisodeContribution[]
    }
    trigger: any
    container: any
  } {
    let [trigger, container] = usePopper({
      placement: 'bottom-end',
      strategy: 'fixed',
      modifiers: [
        {
          name: 'offset',
          options: { offset: [0, 10] },
        },
        {
          name: 'sameWidth',
          enabled: true,
          fn: ({ state }) => {
            const width = get(trigger, ['value', 'width'], 0)

            state.styles.popper.width = `${width}px`
          },
          phase: 'beforeWrite',
          requires: ['computeStyles'],
        },
      ],
    })

    return {
      dispatch: injectStore().dispatch,
      state: mapState({
        contributors: selectors.contributors.contributors,
        episodeContributions: selectors.episode.contributions,
      }),
      trigger,
      container,
    }
  },
  components: {
    CheckIcon,
    XIcon,
    ChevronDownIcon,
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxLabel,
    ComboboxOption,
    ComboboxOptions,
    UserCircleIcon,
    Image,
  },
  computed: {
    filteredContributors() {
      return [
        ...(this.query.length > 0
          ? [
              {
                id: null,
                avatar: null,
                realname: `${this.__('Create: ')}${this.query}`,
              },
            ]
          : []),
        ...this.state.contributors
          .filter(
            (contributor) =>
              !this.state.episodeContributions.some(
                (episodeContribution) =>
                  episodeContribution.contributor_id &&
                  episodeContribution.contributor_id.toString() === contributor.id.toString()
              )
          )
          .filter(
            (contributor) =>
              !this.query ||
              (contributor?.realname || '').toUpperCase().includes(this.query.toUpperCase())
          ),
      ]
    },
  },
  watch: {
    data(value) {
      if (value.id) {
        this.$emit('addContributor', value)
      } else {
        this.$emit('createContributor', this.query)
      }
    },
  },
  methods: {
    filterContributors(event: Event) {
      this.query = get(event, ['target', 'value'], '')
    },
    close() {
      this.$emit('close')
    },
  },
})
</script>
