<template>
  <label for="assets" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">{{
    __('Assets', 'podlove-podcasting-plugin-for-wordpress')
  }}</label>
  <div class="mt-2 sm:col-span-2 sm:mt-0">
    <div v-if="hasFiles">
      <table class="min-w-full table-fixed divide-y divide-gray-300">
        <thead>
          <tr>
            <th scope="col" class="py-3.5 pl-3 text-left text-sm font-semibold text-gray-900">
              {{ __('Enable', 'podlove-podcasting-plugin-for-wordpress') }}
            </th>
            <th scope="col" class="py-3.5 pr-3 text-left text-sm font-semibold text-gray-900">
              {{ __('Asset', 'podlove-podcasting-plugin-for-wordpress') }}
            </th>
            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
              {{ __('File', 'podlove-podcasting-plugin-for-wordpress') }}
            </th>
            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="file in files" :key="file.asset_id" :class="{ 'opacity-50': !file.enable }">
            <td class="relative px-7 sm:w-12 sm:px-6">
              <div
                v-if="file.is_verifying"
                class="inline-flex items-center animate-pulse text-green-600 absolute left-[-14px] top-1/2 -mt-2.5"
              >
                <CloudIcon class="h-5 w-5" aria-hidden="true" />
              </div>
              <input
                type="checkbox"
                class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                :value="file.asset_id"
                :checked="file.enable"
                @click="handleToggle"
              />
            </td>
            <td class="text-sm">
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
                    :class="{
                      'text-gray-400': !file.enable,
                      'text-red-400': file.enable,
                    }"
                    aria-hidden="true"
                  />
                  <!-- fixme: on load, when an asset is disabled, it shows always "file not found"
(because Publisher does not know about disabled files). Only on "verify" do we
get the actual state. => at least show nothing unless we're sure it's "file not
found" -->
                  <span
                    v-if="!file.size"
                    :class="{
                      'text-gray-400': !file.enable,
                      'text-red-400': file.enable,
                    }"
                    >{{ __('File not found', 'podlove-podcasting-plugin-for-wordpress') }}</span
                  >
                  <span v-else>{{ fileSize(file) }}</span>
                </span>
              </div>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <button
                type="button"
                class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white"
                @click="() => handleVerify(file.asset_id)"
              >
                {{ __('Verify', 'podlove-podcasting-plugin-for-wordpress') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p class="mt-3 text-sm leading-6 text-gray-600">{{ __('Duration:', 'podlove-podcasting-plugin-for-wordpress') }} {{ duration }}</p>
    </div>
    <AssetsEmptyState v-else />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { selectors } from '@store'

import { injectStore, mapState } from 'redux-vuex'
import * as mediafiles from '@store/mediafiles.store'

import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/solid'

import { CloudIcon } from '@heroicons/vue/24/outline'

import Timestamp from '@lib/timestamp'
import AssetsEmptyState from './AssetsEmptyState.vue'

export default defineComponent({
  components: {
    CheckCircleIcon,
    XCircleIcon,
    CloudIcon,
    AssetsEmptyState,
  },

  setup() {
    return {
      state: mapState({
        files: selectors.mediafiles.files,
        duration: selectors.episode.duration,
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
    handleVerify(asset_id: number): void {
      this.dispatch(mediafiles.verify(asset_id))
    },
    fileSize(file: mediafiles.MediaFile): string {
      const bytes = file.size

      if (!bytes || bytes < 1) {
        return '???'
      }

      var kilobytes = bytes / 1024

      if (kilobytes < 500) {
        return kilobytes.toFixed(2) + ' kB'
      }

      var megabytes = kilobytes / 1024
      return megabytes.toFixed(2) + ' MB'
    },
  },

  computed: {
    files(): mediafiles.MediaFile[] {
      return this.state.files
    },
    hasFiles(): boolean {
      return this.files.length > 0
    },
    duration(): string {
      const unknownDuration = '--:--:--.---'

      if (!this.state.duration) {
        return unknownDuration
      }

      const timestamp = Timestamp.fromString(this.state.duration)

      if (timestamp.totalMs === 0) {
        return unknownDuration
      }

      return timestamp.pretty
    },
  },
})
</script>
