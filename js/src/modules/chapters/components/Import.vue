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
        class="bg-blue-600 text-white rounded px-4 py-2 hover:bg-blue-300 mb-5"
        type="button"
      >
        Import Chapters
      </button>
      <div class="text-xs text-gray-500 italic">
        Accepts:
        <a
          class="text-blue-500 underline"
          href="https://podlove.org/simple-chapters/"
          target="_blank"
          >Podlove Simple Chapters</a
        >
        (<code>.psc</code>),
        <a class="text-blue-500 underline" href="http://www.audacityteam.org" target="_blank"
          >Audacity</a
        >
        Track Labels,
        <a class="text-blue-500 underline" href="https://hindenburg.com" target="_blank"
          >Hindenburg</a
        >
        project files and MP4Chaps (<code>.txt</code>)
      </div>
    </div>

    <input ref="import" type="file" @change="importChapters" class="hidden" />
  </form>
</template>

<script lang="ts">
import { get } from 'lodash'
import { injectStore } from 'redux-vuex'
import { parse as parseChapters } from '@store/chapters.store'
import { defineComponent } from '@vue/runtime-core';

export default defineComponent({
  setup() {
    return {
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    simulateImportClick() {
      ;(this.$refs.import as HTMLInputElement).click()
    },
    importChapters() {
      const fileInput = this.$refs.import as HTMLInputElement

      if (!fileInput) {
        return
      }

      try {
        const reader: any = new FileReader()

        reader.onload = (event: any) => {
          this.dispatch(parseChapters(event.target.result))
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
});
</script>

<style>
</style>
