<template>
  <module name="mediafiles" title="Media Files">
    <div class="max-w-5xl">
      <div class="w-full flex justify-center m-12 text-center" v-if="isInitializing">
        <div class="animate-pulse mt-4 flex space-x-4">
          <RefreshIcon class="animate-spin h-5 w-5 mr-3" />
          {{ __('Loading...') }}
        </div>
      </div>
      <div v-else>
        <table class="min-w-full table-fixed divide-y divide-gray-300">
          <thead>
            <tr>
              <th scope="col" class="relative px-7 sm:w-12 sm:px-6"></th>
              <th scope="col" class="py-3.5 pr-3 text-left text-sm font-semibold text-gray-900">
                Asset
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                File
              </th>
              <th
                scope="col"
                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
              ></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="file in files" :key="file.asset_id" :class="{ 'opacity-50': !file.enable }">
              <td class="relative px-7 sm:w-12 sm:px-6">
                <!-- <div
                  class="absolute inset-y-0 left-0 w-0.5"
                  :class="{ 'bg-green-400': file.size > 0, 'bg-red-400': !file.size }"
                ></div> -->
                <input
                  type="checkbox"
                  class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                  :value="file.asset_id"
                  :checked="file.enable"
                  @click="handleToggle"
                />
              </td>
              <td>
                {{ file.asset }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                <div class="flex flex-col">
                  <a :href="file.url" target="_blank" class="text-gray-700 underline">{{
                    file.url
                  }}</a>
                  <span class="flex">
                    <CheckCircleIcon
                      v-if="file.size > 0"
                      class="mr-1.5 h-5 w-5 flex-shrink-0 text-green-400"
                      aria-hidden="true"
                    />
                    <XCircleIcon
                      v-else
                      class="mr-1.5 h-5 w-5 flex-shrink-0"
                      :class="{ 'text-gray-400': !file.enable, 'text-red-400': file.enable }"
                      aria-hidden="true"
                    />

                    <span
                      v-if="!file.size"
                      :class="{ 'text-gray-400': !file.enable, 'text-red-400': file.enable }"
                      >File not found</span
                    >
                    <span v-else>{{ (file.size / 1024 / 1024).toFixed(2) }} MB</span>
                  </span>
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                <button
                  type="button"
                  class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white"
                >
                  Verify
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { selectors } from '@store'

import { injectStore, mapState } from 'redux-vuex'
import * as mediafiles from '@store/mediafiles.store'
import Module from '@components/module/Module.vue'

import { RefreshIcon } from '@heroicons/vue/outline'

import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/solid'

export default defineComponent({
  components: {
    Module,
    RefreshIcon,
    CheckCircleIcon,
    XCircleIcon,
  },

  setup() {
    return {
      state: mapState({
        isInitializing: selectors.mediafiles.isInitializing,
        files: selectors.mediafiles.files,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    handleToggle(event: Event): void {
      const input = event.target as HTMLInputElement
      const enable = input.checked
      const asset_id = parseInt(input.value)

      if (enable) {
        this.dispatch(mediafiles.enable(asset_id))
      } else {
        this.dispatch(mediafiles.disable(asset_id))
      }
    },
  },

  computed: {
    isInitializing(): boolean {
      return this.state.isInitializing
    },
    files(): mediafiles.MediaFile[] {
      return this.state.files
    },
  },

  created() {
    this.dispatch(mediafiles.init())
  },
})
</script>
