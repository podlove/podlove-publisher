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
          <podlove-button v-if="!state.selectedFiles || state.selectedFiles.length === 0" variant="primary" @click.prevent="triggerFileInput">
            <upload-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" />
            {{ __('Select Files for Upload', 'podlove-podcasting-plugin-for-wordpress') }}
          </podlove-button>
        </div>

        <!-- File Details Area -->
        <div v-if="state.selectedFiles && state.selectedFiles.length > 0">
          <div class="space-y-3">
            <div
              v-for="(fileInfo, index) in state.selectedFiles"
              :key="fileInfo.newName"
              class="flex items-start space-x-3 p-3 bg-gray-100 rounded-lg"
            >
              <!-- File Icon -->
              <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                  <document-text-icon class="w-6 h-6 text-indigo-500" />
                </div>
              </div>

              <!-- File Info -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">
                  {{ fileInfo.file.name }}
                </p>
                <p v-if="fileInfo.originalName !== fileInfo.newName" class="text-xs text-gray-500">
                  {{ __('Original name:', 'podlove-podcasting-plugin-for-wordpress') }}
                  {{ fileInfo.originalName }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ (fileInfo.file.size / 1024 / 1024).toFixed(2) }} MB
                </p>

                <!-- Progress Bar -->
                <div class="mt-2 w-full bg-white rounded-full h-1.5">
                  <div
                    class="bg-indigo-600 h-1.5 rounded-full progress-transition"
                    :style="{ width: (getUploadProgress(fileInfo.file.name) || 0) + '%' }"
                  ></div>
                </div>

                <!-- Progress Status -->
                <div class="flex justify-between items-center mt-1">
                  <p class="text-xs text-gray-500">
                    <span v-if="getUploadStatus(fileInfo.file.name) == 'init'">Ready to upload</span>
                    <span v-else-if="getUploadStatus(fileInfo.file.name) == 'in_progress'">Uploading...</span>
                    <span v-else-if="getUploadStatus(fileInfo.file.name) == 'finished'">Done!</span>
                    <span v-else-if="getUploadStatus(fileInfo.file.name) == 'error'">Error: {{ getUploadMessage(fileInfo.file.name) }}</span>
                  </p>
                  <p
                    v-if="getUploadStatus(fileInfo.file.name) == 'in_progress'"
                    class="text-xs font-medium text-indigo-600"
                  >
                    {{ getUploadProgress(fileInfo.file.name) }}%
                  </p>
                </div>

                <!-- File Exists Warning -->
                <div
                  v-if="fileInfo?.fileExists && getUploadStatus(fileInfo.file.name) != 'finished'"
                  class="mt-2 flex items-center text-yellow-600"
                >
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
                v-if="getUploadStatus(fileInfo.file.name) != 'in_progress'"
                class="flex-shrink-0 text-gray-400 hover:text-gray-600 focus:outline-none"
                @click="removeFile(fileInfo.newName)"
              >
                <x-mark-icon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <input
          id="plus-file-upload"
          name="plus-file-upload"
          type="file"
          multiple
          class="sr-only"
          ref="fileInput"
          @input="handleFileSelection"
        />
      </label>

      <podlove-button
        v-if="state.selectedFiles && state.selectedFiles.length > 0 && hasFilesReadyToUpload"
        variant="primary"
        @click="plusUploadIntent"
        class="ml-1 mt-3"
      >
        <upload-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" />
        {{ __('Upload Media Files', 'podlove-podcasting-plugin-for-wordpress') }}
      </podlove-button>

      <podlove-button
        v-if="state.selectedFiles && state.selectedFiles.length > 0"
        variant="secondary"
        @click="selectAnotherFile"
        class="ml-1 mt-3"
      >
        <plus-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" />
        {{ __('Add more Files', 'podlove-podcasting-plugin-for-wordpress') }}
      </podlove-button>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'

import { plusUploadIntent } from '@store/mediafiles.store'
import PodloveButton from '@components/button/Button.vue'
import { CloudArrowUpIcon as UploadIcon, PlusIcon, DocumentTextIcon, XMarkIcon } from '@heroicons/vue/24/outline'
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
    DocumentTextIcon,
    XMarkIcon,
    PlusIcon,
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
        selectedFiles: (state: State) => selectors.mediafiles.selectedFiles(state),
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    plusUploadIntent() {
      if (this.state.selectedFiles && this.state.selectedFiles.length > 0) {
        this.state.selectedFiles.forEach((fileInfo: FileInfo) => {
          this.dispatch(plusUploadIntent(fileInfo.file))
        })
      }
    },
    handleFileSelection(event: Event): void {
      const fileList = (event.target as HTMLInputElement).files
      if (!fileList || fileList.length === 0) {
        return
      }

      const filesArray = Array.from(fileList)
      const episodeSlug = this.state.episodeSlug

      const existingFiles = this.state.selectedFiles || []
      const existingFileObjects = existingFiles.map((fileInfo: FileInfo) => fileInfo.file)
      const allFiles = [...existingFileObjects, ...filesArray]

      this.dispatch(mediafiles.fileSelected(allFiles, episodeSlug))
    },
    resetFiles() {
      this.dispatch({ type: mediafiles.SET_FILE_INFO, payload: [] })
    },
    removeFile(fileName: string) {
      this.dispatch(mediafiles.removeSelectedFile(fileName))
    },
    triggerFileInput() {
      const fileInput = this.$refs.fileInput as HTMLInputElement
      if (fileInput) {
        fileInput.click()
      }
    },
    selectAnotherFile() {
      this.triggerFileInput()
    },
    getUploadProgress(fileName: string): number | null {
      const key = `plus-upload-${fileName}`
      return this.state.progress(key) || null
    },
    getUploadStatus(fileName: string): string | null {
      const key = `plus-upload-${fileName}`
      return this.state.status(key) || null
    },
    getUploadMessage(fileName: string): string | null {
      const key = `plus-upload-${fileName}`
      return this.state.message(key) || null
    },
  },

  computed: {
    selectedFiles(): FileInfo[] {
      return this.state.selectedFiles || []
    },
    hasFilesReadyToUpload(): boolean {
      return this.selectedFiles.some(fileInfo => {
        const status = this.getUploadStatus(fileInfo.file.name)
        return status === 'init' || status === null
      })
    },
    allFilesUploaded(): boolean {
      return this.selectedFiles.length > 0 && this.selectedFiles.every(fileInfo => {
        const status = this.getUploadStatus(fileInfo.file.name)
        return status === 'finished'
      })
    },
  },
})
</script>
