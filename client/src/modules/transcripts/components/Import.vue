<template>
  <form ref="importForm" class="cursor-pointer">
    <div class="grid grid-cols-2">
      <div>
        <podlove-button variant="primary" @click="simulateImportClick" class="ml-1">
          <upload-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> {{ __('Import Transcript', 'podlove-podcasting-plugin-for-wordpress') }}
        </podlove-button>
        <input ref="import" accept="text/vtt" type="file" @change="handleImport" class="hidden" key=""/>
      </div>
      <div>
        <podlove-button
          variant="primary"
          @click="importTranscriptFromAsset"
          class="ml-1 min-w-[120px]"
          :disabled="state.isImportingFromAsset"
        >
          <arrow-path-icon
            v-if="state.isImportingFromAsset"
            class="-ml-0.5 mr-2 h-4 w-4 animate-spin"
            aria-hidden="true"
          />
          <document-text-icon v-else class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" />
          {{
            state.isImportingFromAsset
              ? __('Getting...', 'podlove-podcasting-plugin-for-wordpress')
              : __('Get From Asset', 'podlove-podcasting-plugin-for-wordpress')
          }}
        </podlove-button>
      </div>
    </div>
    <div
      v-if="state.assetImportError"
      class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
      role="alert"
    >
      {{ state.assetImportError }}
    </div>
  </form>
</template>

<script lang="ts">
import { get } from 'lodash'
import { defineComponent } from '@vue/runtime-core'
import {
  ArrowPathIcon,
  CloudArrowUpIcon as UploadIcon,
  DocumentTextIcon,
} from '@heroicons/vue/24/outline'

import PodloveButton from '@components/button/Button.vue'
import { importTranscripts, importTranscriptFromAsset } from '@store/transcripts.store'
import selectors from '@store/selectors'
import { injectAppDispatch, mapAppState } from '@store/vue'

export default defineComponent({
  props: {
    outlet: {
      type: String,
      default: 'header'
    }
  },

  components: {
    ArrowPathIcon, PodloveButton, UploadIcon, DocumentTextIcon
  },

  setup() {
    return {
      state: mapAppState({
        isImportingFromAsset: selectors.transcripts.isImportingFromAsset,
        assetImportError: selectors.transcripts.assetImportError,
      }),
      dispatch: injectAppDispatch(),
    }
  },

  methods: {
    simulateImportClick() {
      ;(this.$refs.import as HTMLInputElement).click()
    },
    importTranscriptFromAsset() {
      if (this.state.isImportingFromAsset) {
        return
      }

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
