<template>
  <module name="mediafiles" title="Media Files">
    <div>
      <div class="w-full flex justify-center m-12 text-center" v-if="isInitializing">
        <div class="animate-pulse mt-4 flex space-x-4">
          <RefreshIcon class="animate-spin h-5 w-5 mr-3" />
          {{ __('Loading...') }}
        </div>
      </div>
      <div v-else>
        <div class="px-6">
          <div
            class="mt-10 sm:mt-0 space-y-8 border-b border-gray-900/10 pb-12 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:pb-0"
          >
            <div class="sm:grid sm:grid-cols-[175px_auto_auto] sm:items-start sm:gap-4 sm:py-6">
              <MediaSlug />
            </div>

            <div class="sm:grid sm:grid-cols-[175px_auto_auto] sm:items-start sm:gap-4 sm:py-6">
              <label
                for="assets"
                class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5"
                >Assets</label
              >
              <div class="mt-2 sm:col-span-2 sm:mt-0">
                <table class="min-w-full table-fixed divide-y divide-gray-300">
                  <thead>
                    <tr>
                      <th
                        scope="col"
                        class="py-3.5 pl-3 text-left text-sm font-semibold text-gray-900"
                      >
                        Enable
                      </th>
                      <th
                        scope="col"
                        class="py-3.5 pr-3 text-left text-sm font-semibold text-gray-900"
                      >
                        Asset
                      </th>
                      <th
                        scope="col"
                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                      >
                        File
                      </th>
                      <th
                        scope="col"
                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                      ></th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 bg-white">
                    <tr
                      v-for="file in files"
                      :key="file.asset_id"
                      :class="{ 'opacity-50': !file.enable }"
                    >
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
                              >File not found</span
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
                          Verify
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <p class="mt-3 text-sm leading-6 text-gray-600">Episode Duration: {{ duration }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- filename / slug -->

        <!-- media file table -->
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

import MediaSlug from './components/MediaSlug.vue'

import Timestamp from '@lib/timestamp'

export default defineComponent({
  components: {
    Module,
    RefreshIcon,
    CheckCircleIcon,
    XCircleIcon,
    MediaSlug,
  },

  setup() {
    return {
      state: mapState({
        isInitializing: selectors.mediafiles.isInitializing,
        files: selectors.mediafiles.files,
        duration: selectors.episode.duration,
        baseUri: selectors.settings.mediaFileBaseUri,
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
    isInitializing(): boolean {
      return this.state.isInitializing
    },
    files(): mediafiles.MediaFile[] {
      return this.state.files
    },
    duration(): string {
      if (!this.state.duration) {
        return '-'
      }

      return Timestamp.fromString(this.state.duration).pretty
    },
    assetPrefix(): string {
      return this.state.baseUri?.replace(/https?:\/\//i, '')
    },
  },

  created() {
    this.dispatch(mediafiles.init())
  },
})
</script>
