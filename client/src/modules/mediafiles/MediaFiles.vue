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
        <!-- filename / slug -->
        <div
          class="ml-3 space-y-8 border-b border-gray-900/10 pt-6 sm:pt-0 pb-12 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0"
        >
          <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:py-6">
            <label
              for="filename_slug"
              class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5"
              >Filename / Slug</label
            >
            <div class="mt-2 sm:col-span-2 sm:mt-0">
              <div
                class="flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-600 sm:max-w-md"
              >
                <span class="flex select-none items-center pl-3 text-gray-500 sm:text-sm">{{
                  assetPrefix
                }}</span>
                <input
                  type="text"
                  name="filename_slug"
                  id="filename_slug"
                  autocomplete="filename_slug"
                  class="block flex-1 border-0 bg-transparent py-1.5 pl-1 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6"
                  placeholder=""
                  :value="slug"
                  @input="updateSlug($event)"
                />
                <span class="flex slect-none items-center text-gray-500 sm:text-sm pr-2">.ext</span>
              </div>
            </div>
          </div>
        </div>

        <!-- media file table -->
        <table class="min-w-full table-fixed divide-y divide-gray-300">
          <thead>
            <tr>
              <th scope="col" class="py-3.5 pl-3 text-left text-sm font-semibold text-gray-900">
                Enable
              </th>
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
                    <!-- fixme: on load, when an asset is disabled, it shows always "file not found"
(because Publisher does not know about disabled files). Only on "verify" do we
get the actual state. => at least show nothing unless we're sure it's "file not
found" -->
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
                  @click="() => handleVerify(file.asset_id)"
                >
                  Verify
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="m-3">
        <span class="text-sm text-gray-700">Episode Duration: {{ duration }}</span>
      </div>
    </div>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { selectors } from '@store'

import { injectStore, mapState } from 'redux-vuex'
import * as mediafiles from '@store/mediafiles.store'
import { update as updateEpisode } from '@store/episode.store'
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
        duration: selectors.episode.duration,
        slug: selectors.episode.slug,
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
    updateSlug(event: Event) {
      this.dispatch(
        updateEpisode({ prop: 'slug', value: (event.target as HTMLInputElement).value })
      )
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
      return this.state.duration
    },
    slug(): string {
      return this.state.slug
    },
    assetPrefix(): string {
      return 'https://files.podlovers.org/'
    },
  },

  created() {
    this.dispatch(mediafiles.init())
  },
})
</script>
