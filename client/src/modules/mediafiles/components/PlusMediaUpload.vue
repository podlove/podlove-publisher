<template>
  <label
    for="mediafile_upload"
    class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5"
    >{{ __('PLUS Upload', 'podlove-podcasting-plugin-for-wordpress') }}</label
  >
  <div class="mt-2 sm:col-span-2 sm:mt-0">
    <div>
      <podlove-button variant="primary" @click="plusUploadIntent" class="ml-1">
        <upload-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" />
        {{ __('Upload Media File', 'podlove-podcasting-plugin-for-wordpress') }}
      </podlove-button>

      <input type="file" name="plus-file-upload" @input="handleFileSelection" />

      <!-- Upload progress bar -->
      <div v-if="uploadProgress != null">
        <div class="mt-2" aria-hidden="true">
          <div class="overflow-hidden rounded-full bg-gray-100">
            <div
              class="h-2 rounded-full bg-indigo-600"
              :style="{ width: uploadProgress + '%' }"
            ></div>
          </div>
          <div class="mt-1 hidden grid-cols-4 text-sm font-medium text-gray-600 sm:grid">
            <div>{{ uploadProgress }}%</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
// TODO: use "choose file" UI from Auphonic
// TODO: immediately show progress spinner on button click
// TODO: show error message if upload fails

import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'

import { plusUploadIntent } from '@store/mediafiles.store'
import PodloveButton from '@components/button/Button.vue'
import { CloudArrowUpIcon as UploadIcon } from '@heroicons/vue/24/outline'
import { State, selectors } from '@store'

export default defineComponent({
  components: {
    PodloveButton,
    UploadIcon,
  },
  data() {
    return { file: null as File | null }
  },
  setup() {
    return {
      state: mapState({
        progress: (state: State) => (key: string) => selectors.progress.progress(state, key),
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    plusUploadIntent() {
      if (this.file) {
        this.dispatch(plusUploadIntent(this.file))
      }
    },
    handleFileSelection(event: Event): void {
      const files = (event.target as HTMLInputElement).files
      this.file = files ? files[0] : null
    },
  },

  computed: {
    uploadProgress(): number | null {
      if (!this.file) return null
      const progressKey = `plus-upload-${this.file.name}`
      return this.state.progress(progressKey) || null
    },
  },
})
</script>
