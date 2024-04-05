<template>
  <div v-if="state.transcripts.length > 0">
    <popover>
      <template v-slot:trigger>
        <podlove-button variant="secondary" size="small" tabindex="-1">{{ __('Export', 'podlove-podcasting-plugin-for-wordpress') }}</podlove-button>
      </template>
      <template v-slot:content>
        <div
          class="bg-white p-7 pb-1 -translate-x-full z-10 mt-3 transform ml-16 rounded-lg shadow-lg"
        >
          <a
            v-for="(exportType, index) in exportTypes"
            :key="`type-${index}`"
            :download="exportType.file"
            :href="`${state.baseUrl}/?p=${state.post}&podlove_transcript=${exportType.type}`"
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
              {{ exportType.title }}
            </div>
            <div class="text-sm text-gray-500">{{ exportType.ending }}</div>
          </a>
        </div>
      </template>
    </popover>
  </div>
</template>

<script lang="ts">
import { mapState } from 'redux-vuex'
import selectors from '@store/selectors'
import { defineComponent } from '@vue/runtime-core'

import Popover from '@components/popover/Popover.vue'
import PodloveButton from '@components/button/Button.vue'

const exportTypes = [
  {
    title: 'Export webvtt',
    type: 'webvtt',
    file: 'transcript.webvtt',
    ending: '.webvtt',
  },
  {
    title: 'Export json (flat)',
    type: 'json',
    file: 'transcript.json',
    ending: '.json',
  },
  {
    title: 'Export json (grouped)',
    type: 'json_grouped',
    file: 'transcript.json',
    ending: '.json',
  },
  {
    title: 'Export xml',
    type: 'xml',
    file: 'transcript.xml',
    ending: '.xml',
  },
]

export default defineComponent({
  components: {
    Popover,
    PodloveButton,
  },

  setup() {
    return {
      state: mapState({
        post: selectors.post.id,
        baseUrl: selectors.runtime.baseUrl,
        transcripts: selectors.transcripts.list,
      }),
    }
  },

  data() {
    return {
      exportTypes,
    }
  },
})
</script>

<style></style>
