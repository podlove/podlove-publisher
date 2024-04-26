<template>
  <div>
    <popover>
      <template v-slot:trigger>
        <podlove-button variant="secondary" size="small">{{ __('Export', 'podlove-podcasting-plugin-for-wordpress') }}</podlove-button>
      </template>
      <template v-slot:content>
        <div class="bg-white p-7 -translate-x-full z-10 mt-3 transform ml-16 rounded-lg shadow-lg">
          <a
            @click="download('psc')"
            class="
              flex
              items-center
              p-2
              -m-3
              transition
              duration-150
              ease-in-out
              rounded-lg
              cursor-pointer
              bg-gray-100
              hover:bg-indigo-100
              focus:outline-none
              focus-visible:ring focus-visible:ring-indigo-500 focus-visible:ring-opacity-50
              mb-4
            "
          >
            <div class="text-sm font-medium text-gray-900 truncate w-full mr-2">
              {{ __('Podlove Simple Chapters', 'podlove-podcasting-plugin-for-wordpress') }}
            </div>
            <div class="text-sm text-gray-500">.psc</div>
          </a>
          <a
            @click="download('mp4')"
            class="
              flex
              items-center
              p-2
              -m-3
              transition
              duration-150
              ease-in-out
              rounded-lg
              cursor-pointer
              bg-gray-100
              hover:bg-indigo-100
              focus:outline-none
              focus-visible:ring focus-visible:ring-indigo-500 focus-visible:ring-opacity-50
            "
          >
            <div class="text-sm font-medium text-gray-900 truncate w-full mr-2">{{ __('MP4Chaps', 'podlove-podcasting-plugin-for-wordpress') }}</div>
            <div class="text-sm text-gray-500">.txt</div>
          </a>
        </div>
      </template>
    </popover>
  </div>
</template>

<script lang="ts">
import { injectStore } from 'redux-vuex'
import { defineComponent } from '@vue/runtime-core'
import { download } from '@store/chapters.store'
import Popover from '@components/popover/Popover.vue'
import PodloveButton from '@components/button/Button.vue'

export default defineComponent({
  components: {
    Popover,
    PodloveButton,
  },
  setup(): { dispatch: Function } {
    return {
      dispatch: injectStore().dispatch,
    }
  },
  methods: {
    download(type: 'psc' | 'mp4') {
      this.dispatch(download(type))
    },
  },
})
</script>

<style></style>
