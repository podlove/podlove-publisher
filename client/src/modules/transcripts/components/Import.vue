<template>
  <form ref="importForm" class="cursor-pointer">
    <div class="grid grid-cols-2">
      <div>
        <podlove-button variant="primary" @click="simulateImportClick" class="ml-1">
          <upload-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> {{ __('Import Transcript') }}
        </podlove-button>
        <input ref="import" accept="text/vtt" type="file" @change="handleImport" class="hidden" key=""/>
      </div>
      <div>
        <podlove-button variant="primary" @click="importTranscriptFromAsset" class="ml-1">
          {{ __('Get From Asset') }}
        </podlove-button>
      </div>
    </div>
  </form>
</template>

<script lang="ts">
import { get } from 'lodash'
import { injectStore } from 'redux-vuex'
import { defineComponent } from '@vue/runtime-core'
import { CloudArrowUpIcon as UploadIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'

import PodloveButton from '@components/button/Button.vue'
import { importTranscripts, importTranscriptFromAsset } from '@store/transcripts.store'

export default defineComponent({
  props: {
    outlet: {
      type: String,
      default: 'header'
    }
  },

  components: {
    PodloveButton, UploadIcon, DocumentTextIcon
  },

  setup() {
    return {
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    simulateImportClick() {
      ;(this.$refs.import as HTMLInputElement).click()
    },
    importTranscriptFromAsset() {
      this.dispatch(importTranscriptFromAsset())
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
          .readAsText(get(fileInput, ['files', 0], ''))(
            // reset import element
            this.$refs.importForm as HTMLFormElement
          )
          .reset()
      } catch (err) {}
    },
  },
})
</script>

<style>
</style>
