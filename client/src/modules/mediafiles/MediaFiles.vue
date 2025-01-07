<template>
  <module name="mediafiles" title="Media Files">
    <div>
      <div class="w-full flex justify-center m-12 text-center" v-if="isInitializing">
        <div class="animate-pulse mt-4 flex space-x-4">
          <RefreshIcon class="animate-spin h-5 w-5 mr-3" />
          {{ __('Loading...', 'podlove-podcasting-plugin-for-wordpress') }}
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

            <div
              v-if="isPlusStorageEnabled"
              class="sm:grid sm:grid-cols-[175px_auto_auto] sm:items-start sm:gap-4 sm:py-6"
            >
              <PlusMediaUpload />
            </div>
            <div
              v-else-if="isMediaUploadEnabled"
              class="sm:grid sm:grid-cols-[175px_auto_auto] sm:items-start sm:gap-4 sm:py-6"
            >
              <MediaUpload />
            </div>

            <div class="sm:grid sm:grid-cols-[175px_auto_auto] sm:items-start sm:gap-4 sm:py-6">
              <AssetsTable />
            </div>
          </div>
        </div>
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

import { ArrowPathIcon as RefreshIcon } from '@heroicons/vue/24/outline'

import MediaSlug from './components/MediaSlug.vue'
import MediaUpload from './components/MediaUpload.vue'
import PlusMediaUpload from './components/PlusMediaUpload.vue'
import AssetsTable from './components/AssetsTable.vue'

export default defineComponent({
  components: {
    Module,
    RefreshIcon,
    MediaSlug,
    MediaUpload,
    PlusMediaUpload,
    AssetsTable,
  },

  setup() {
    return {
      state: mapState({
        isInitializing: selectors.mediafiles.isInitializing,
        modules: selectors.settings.modules,
        plusStorageEnabled: selectors.settings.enablePlusStorage,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  computed: {
    isInitializing(): boolean {
      return this.state.isInitializing
    },
    isMediaUploadEnabled(): boolean {
      return this.state.modules?.includes('wordpress_file_upload')
    },
    isPlusStorageEnabled(): boolean {
      return this.state.plusStorageEnabled
    },
  },

  created() {
    this.dispatch(mediafiles.init())
  },
})
</script>
