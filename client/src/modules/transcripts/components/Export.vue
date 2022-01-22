<template>
  <div class="flex justify-center items-center p-4">
    <a
      class="bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-300 block mx-2"
      v-for="(exportType, index) in exportTypes"
      :key="`type-${index}`"
      :download="exportType.file"
      :href="`${state.baseUrl}/?p=${state.post}&podlove_transcript=${exportType.type}`"
      >{{ exportType.title }}</a
    >
  </div>
</template>

<script lang="ts">
import { mapState } from 'redux-vuex'
import selectors from '@store/selectors'
import { defineComponent } from '@vue/runtime-core'

const exportTypes = [
  {
    title: 'Export webvtt',
    type: 'webvtt',
    file: 'transcript.webvtt',
  },
  {
    title: 'Export json (flat)',
    type: 'json',
    file: 'transcript.json',
  },
  {
    title: 'Export json (grouped)',
    type: 'json_grouped',
    file: 'transcript.json',
  },
  {
    title: 'Export xml',
    type: 'xml',
    file: 'transcript.xml',
  },
]

export default defineComponent({
  setup() {
    return {
      state: mapState({
        post: selectors.post.id,
        baseUrl: selectors.runtime.baseUrl,
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

<style>
</style>
