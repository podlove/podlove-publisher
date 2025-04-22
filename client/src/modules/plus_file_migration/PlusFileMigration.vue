<template>
  <div class="m-3 rounded-lg bg-white">
    <section class="bg-white w-full" v-if="uiState === 'init'">
      <div class="text-center">loading...</div>
    </section>

    <section class="bg-white w-full" v-if="uiState === 'finished'">
      <div class="text-center py-10 px-5">
        <div
          class="w-20 h-20 mx-auto mb-5 bg-green-50 rounded-full flex items-center justify-center"
        >
          <CheckBadgeIcon class="w-10 h-10 stroke-green-500" />
        </div>
        <h3 class="text-lg font-medium text-gray-800 mb-2.5">Upload Complete!</h3>
        <p class="text-gray-600 text-sm mb-6 max-w-md mx-auto">
          All your files have been successfully uploaded.
        </p>

        <div class="bg-gray-50 rounded-lg p-5 mx-auto m-5 text-left">
          <div class="text-base font-medium text-gray-800 mb-4">Upload Summary</div>
          <div class="flex justify-between mb-2.5 text-sm text-gray-600">
            <span>Total Episodes:</span>
            <span>{{ totalEpisodes }}</span>
          </div>
          <div class="flex justify-between text-sm text-gray-600">
            <span>Total Files:</span>
            <span>{{ totalFiles }}</span>
          </div>
        </div>

        <p class="text-gray-600 text-left text-sm px-2 mx-auto">
          Starting immediately, your files will be served from PLUS Cloud Storage to all listeners.
          When you create and manage new episodes, files will be directly uploaded to PLUS Cloud
          Storage.<br /><br />Happy podcasting!
        </p>
      </div>
    </section>

    <section class="bg-white w-full" v-if="uiState === 'ready'">
      <div class="text-center py-10 px-5">
        <div
          class="w-20 h-20 mx-auto mb-5 bg-gray-100 rounded-full flex items-center justify-center"
        >
          <UploadIcon class="w-10 h-10 stroke-gray-600" />
        </div>
        <h3 class="text-lg font-medium text-gray-800 mb-2.5">
          Upload Your Existing Media Files to PLUS Cloud Storage
        </h3>
        <p class="text-left text-gray-600 text-sm mb-2.5 max-w-md mx-auto">
          This is a one-time operation to move your existing files to PLUS Cloud Storage. It will
          only need to be done once.
        </p>
        <p class="text-left text-gray-600 text-sm mb-6 max-w-md mx-auto">
          You have {{ totalFiles }} files to upload. Once they are uploaded, you can delete the
          files from your local storage or keep them as a backup.
        </p>
        <podlove-button variant="primary" @click="startMigration">{{
          __('Start Uploads', 'podlove-podcasting-plugin-for-wordpress')
        }}</podlove-button>
      </div>
    </section>

    <section class="bg-white w-full" v-if="uiState === 'in_progress'">
      <div class="py-10 px-5">
        <div class="flex justify-between mb-2 text-sm text-gray-600">
          <span>Progress Uploading Media Files to PLUS Cloud Storage</span>
          <span>{{ progress }}%</span>
        </div>
        <div class="h-2.5 bg-gray-100 rounded-lg overflow-hidden">
          <div
            class="h-full bg-green-500 rounded-lg transition-all duration-300"
            :style="{ width: progress + '%' }"
          ></div>
        </div>

        <section class="bg-gray-50 my-5 p-5 rounded-lg">
          <h3 class="text-base font-medium text-gray-800 mb-2">Currently Uploading</h3>
          <p class="text-gray-600 text-sm mb-1">
            <strong>Episode:</strong> {{ currentEpisodeName }}
          </p>
          <p class="text-gray-600 text-sm"><strong>File:</strong> {{ currentFileName }}</p>
        </section>

        <div class="border-l-4 border-yellow-400 bg-yellow-50 p-4">
          <div class="flex">
            <div class="shrink-0">
              <ExclamationTriangleIcon class="size-5 text-yellow-400" aria-hidden="true" />
            </div>
            <div class="ml-3">
              <p class="text-sm text-yellow-700">
                Keep this window open while the upload is in progress.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { injectStore, mapState } from 'redux-vuex'
import Module from '@components/module/Module.vue'
import * as plusFileMigration from '@store/plusFileMigration.store'
import { selectors } from '@store'

import PodloveButton from '@components/button/Button.vue'

import { CloudArrowUpIcon as UploadIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline'

import { ExclamationTriangleIcon } from '@heroicons/vue/24/solid'

export default defineComponent({
  name: 'PlusFileMigration',
  components: {
    Module,
    PodloveButton,
    UploadIcon,
    CheckBadgeIcon,
    ExclamationTriangleIcon,
  },
  setup() {
    return {
      state: mapState({
        totalState: selectors.plusFileMigration.totalState,
        progress: selectors.plusFileMigration.progress,
        files: selectors.plusFileMigration.episodesWithFiles,
        currentEpisodeName: selectors.plusFileMigration.currentEpisodeName,
        currentFileName: selectors.plusFileMigration.currentFileName,
      }),
      dispatch: injectStore().dispatch,
    }
  },
  created() {
    this.dispatch(plusFileMigration.init())
  },
  methods: {
    startMigration() {
      this.dispatch(plusFileMigration.startMigration())
    },
  },
  computed: {
    progress() {
      return this.state.progress
    },
    totalFiles() {
      return this.state.files.reduce((acc: number, file: any) => acc + file.files.length, 0)
    },
    totalEpisodes() {
      return this.state.files.length
    },
    currentEpisodeName() {
      return this.state.currentEpisodeName
    },
    currentFileName() {
      return this.state.currentFileName
    },
    uiState() {
      return this.state.totalState
    },
  },
})
</script>
