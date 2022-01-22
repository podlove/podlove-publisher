<template>
  <div class="flex justify-center items-center p-4 flex-col">
    <button class="bg-red-600 text-white rounded px-4 py-2 hover:bg-red-300" type="button" @click="deleteTranscripts()">
      Delete Transcripts
    </button>
  </div>
</template>

<script lang="ts">
import { injectStore } from 'redux-vuex'
import { deleteTranscripts } from '@store/transcripts.store'
import { defineComponent } from '@vue/runtime-core'

export default defineComponent({
  setup(): {
    dispatch: Function
  } {
    return {
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    deleteTranscripts() {
      this.dispatch(deleteTranscripts())
    },
  },
})
</script>

<style>
</style>
