<template>
  <div class="flex justify-center items-center">
    <button
      class="bg-blue-600 text-white rounded px-8 py-4 hover:bg-blue-300 m-5"
      @click="download('psc')"
    >
      Podlove Simple Chapters (psc)
    </button>
    <button
      class="bg-blue-600 text-white rounded px-8 py-4 hover:bg-blue-300 m-5"
      @click="download('mp4')"
    >
      MP4Chaps (txt)
    </button>
  </div>
</template>

<script lang="ts">
import { injectStore } from 'redux-vuex'
import { defineComponent } from '@vue/runtime-core'
import { download } from '@store/chapters.store'

export default defineComponent({
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

<style>
</style>
