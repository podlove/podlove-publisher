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
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'

import { plusUploadIntent } from '@store/mediafiles.store'
import PodloveButton from '@components/button/Button.vue'
import { CloudArrowUpIcon as UploadIcon } from '@heroicons/vue/24/outline'

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
      state: mapState({}),
      dispatch: injectStore().dispatch,
    }
  },

  // NOTE: see Auphonic FileChooser for how to deal with file selection/uploads

  methods: {
    plusUploadIntent() {
      this.dispatch(plusUploadIntent(this.file))
    },
    handleFileSelection(event: Event): void {
      const files = (event.target as HTMLInputElement).files
      this.file = files ? files[0] : null
    },
  },

  computed: {},
})
</script>
