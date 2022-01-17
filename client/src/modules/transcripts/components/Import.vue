<template>
  <form ref="importForm" class="cursor-pointer" @click="simulateImportClick">
    <div
      class="
        flex
        justify-center
        items-center
        p-4
        flex-col
        bg-white
        rounded
        border-gray-400 border-2 border-dashed
      "
    >
      <button
        class="bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-300"
        type="button"
      >
        Import Transcripts
      </button>
    </div>

    <input ref="import" accept="text/vtt" type="file" @change="handleImport" class="hidden" />
  </form>
</template>

<script lang="ts">
import { get } from 'lodash'
import { injectStore } from 'redux-vuex'
import { importTranscripts } from '@store/transcripts.store'

export default {
  setup() {
    return {
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    simulateImportClick() {
      ;(this.$refs.import as HTMLInputElement).click()
    },
    handleImport() {
      const fileInput = this.$refs.import as HTMLInputElement

      if (!fileInput) {
        return
      }

      try {
        const reader: any = new FileReader()

        reader.onload = (event: any) => {
          this.dispatch(importTranscripts(event.target.result))
        }

        reader
          .readAsText(get(fileInput, ['files', 0], ''))
          (
            // reset import element
            this.$refs.importForm as HTMLFormElement
          )
          .reset()
      } catch (err) {}
    },
  },
}
</script>

<style>
</style>
