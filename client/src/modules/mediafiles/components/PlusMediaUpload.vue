<template>
  <label
    for="mediafile_upload"
    class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5"
    >{{ __('PLUS Upload', 'podlove-podcasting-plugin-for-wordpress') }}</label
  >
  <div class="mt-2 sm:col-span-2 sm:mt-0">
    <div>
      <label
        for="plus-file-upload"
        class="relative max-w-[400px] flex flex-col gap-2 cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none"
      >
        <div>
          <podlove-button v-if="!fileInfo" variant="primary" @click.prevent="triggerFileInput">
            <upload-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" />
            {{ __('Select File for Upload', 'podlove-podcasting-plugin-for-wordpress') }}
          </podlove-button>
        </div>

        <!-- File Details Area -->
        <div v-if="fileInfo">
          <div class="flex items-start space-x-3 p-3 bg-gray-100 rounded-lg">
            <!-- File Icon -->
            <div class="flex-shrink-0">
              <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                <svg
                  class="w-6 h-6 text-indigo-500"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  ></path>
                </svg>
              </div>
            </div>

            <!-- File Info -->
            <div class="flex-1 min-w-0">
              <p id="fileName" class="text-sm font-medium text-gray-900 truncate">
                {{ fileInfo.file.name }}
              </p>
              <p v-if="fileInfo.originalName !== fileInfo.newName" class="text-xs text-gray-500">
                {{ __('Original name:', 'podlove-podcasting-plugin-for-wordpress') }}
                {{ fileInfo.originalName }}
              </p>
              <p id="fileSize" class="text-xs text-gray-500">
                {{ (fileInfo.file.size / 1024 / 1024).toFixed(2) }} MB
              </p>

              <!-- Progress Bar -->
              <div class="mt-2 w-full bg-white rounded-full h-1.5">
                <div
                  id="progressBar"
                  class="bg-indigo-600 h-1.5 rounded-full progress-transition"
                  :style="{ width: (uploadProgress || 0) + '%' }"
                ></div>
              </div>

              <!-- Progress Status -->
              <div class="flex justify-between items-center mt-1">
                <p id="uploadStatus" class="text-xs text-gray-500">
                  <span v-if="uploadStatus == 'init'">Ready to upload</span>
                  <span v-else-if="uploadStatus == 'in_progress'">Uploading...</span>
                  <span v-else-if="uploadStatus == 'finished'">Done!</span>
                  <span v-else-if="uploadStatus == 'error'">Error: {{ uploadMessage }}</span>
                </p>
                <p
                  v-if="uploadStatus == 'in_progress'"
                  id="progressPercentage"
                  class="text-xs font-medium text-indigo-600"
                >
                  {{ uploadProgress }}%
                </p>
              </div>

              <!-- File Exists Warning -->
              <div v-if="fileInfo?.fileExists" class="mt-2 flex items-center text-yellow-600">
                <span class="text-xs">
                  {{
                    __(
                      'A file with this name already exists and will be overwritten.',
                      'podlove-podcasting-plugin-for-wordpress'
                    )
                  }}
                </span>
              </div>
            </div>

            <!-- Remove Button -->
            <button
              v-if="uploadStatus != 'in_progress'"
              id="removeBtn"
              class="flex-shrink-0 text-gray-400 hover:text-gray-600 focus:outline-none"
              @click="resetFile()"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
            </button>
          </div>
        </div>

        <input
          id="plus-file-upload"
          name="plus-file-upload"
          type="file"
          class="sr-only"
          ref="fileInput"
          @input="handleFileSelection"
        />
      </label>

      <podlove-button
        v-if="fileInfo && uploadStatus == 'init'"
        variant="primary"
        @click="plusUploadIntent"
        class="ml-1 mt-3"
      >
        <upload-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" />
        {{ __('Upload Media File', 'podlove-podcasting-plugin-for-wordpress') }}
      </podlove-button>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'

import { plusUploadIntent } from '@store/mediafiles.store'
import PodloveButton from '@components/button/Button.vue'
import { CloudArrowUpIcon as UploadIcon } from '@heroicons/vue/24/outline'
import { State, selectors } from '@store'
import * as mediafiles from '@store/mediafiles.store'

interface FileInfo {
  file: File
  originalName: string
  newName: string
  fileExists: boolean
}

export default defineComponent({
  components: {
    PodloveButton,
    UploadIcon,
  },
  data() {
    return {}
  },
  setup() {
    return {
      state: mapState({
        progress: (state: State) => (key: string) => selectors.progress.progress(state, key),
        status: (state: State) => (key: string) => selectors.progress.status(state, key),
        message: (state: State) => (key: string) => selectors.progress.message(state, key),
        episodeSlug: (state: State) => selectors.episode.slug(state),
        fileInfo: (state: State) => selectors.mediafiles.fileInfo(state),
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    plusUploadIntent() {
      if (this.state.fileInfo) {
        this.dispatch(plusUploadIntent(this.state.fileInfo.file))
      }
    },
    handleFileSelection(event: Event): void {
      const files = (event.target as HTMLInputElement).files
      if (!files || !files[0]) {
        this.dispatch({ type: mediafiles.SET_FILE_INFO, payload: null })
        return
      }

      const originalFile = files[0]
      const episodeSlug = this.state.episodeSlug

      this.dispatch(mediafiles.fileSelected(originalFile, episodeSlug))
    },
    resetFile() {
      this.dispatch({ type: mediafiles.SET_FILE_INFO, payload: null })
    },
    triggerFileInput() {
      const fileInput = this.$refs.fileInput as HTMLInputElement
      if (fileInput) {
        fileInput.click()
      }
    },
  },

  computed: {
    file(): File | null {
      return this.fileInfo?.file || null
    },
    fileInfo(): FileInfo | null {
      return this.state.fileInfo || null
    },
    uploadKey(): string | null {
      if (!this.fileInfo) return null
      return `plus-upload-${this.fileInfo.file.name}`
    },
    uploadProgress(): number | null {
      if (!this.fileInfo) return null
      return this.state.progress(this.uploadKey) || null
    },
    uploadStatus(): string | null {
      if (!this.fileInfo) return null
      return this.state.status(this.uploadKey) || null
    },
    uploadMessage(): string | null {
      if (!this.fileInfo) return null
      return this.state.message(this.uploadKey) || null
    },
  },
})
</script>
