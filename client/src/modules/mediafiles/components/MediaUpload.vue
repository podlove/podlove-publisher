<template>
  <label
    for="mediafile_upload"
    class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5"
    >{{ __('Upload') }}</label
  >
  <div class="mt-2 sm:col-span-2 sm:mt-0">
    <div>
      <podlove-button variant="primary" @click="uploadIntent" class="ml-1">
        <upload-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" />
        {{ __('Upload Media File') }}
      </podlove-button>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'

import { uploadIntent } from '@store/mediafiles.store'
import PodloveButton from '@components/button/Button.vue'
import { CloudArrowUpIcon as UploadIcon } from '@heroicons/vue/24/outline'

export default defineComponent({
  components: {
    PodloveButton,
    UploadIcon,
  },
  setup() {
    return {
      state: mapState({}),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    uploadIntent() {
      this.dispatch(uploadIntent())
    },
  },

  computed: {},
})
</script>
