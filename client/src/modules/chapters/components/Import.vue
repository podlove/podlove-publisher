<template>
  <tooltip>
    <template v-slot:trigger>
      <podlove-button variant="secondary" size="small" @click="simulateImportClick()"
        >{{ __('Import') }}</podlove-button
      >
      <input ref="import" type="file" @change="importChapters" class="hidden" />
    </template>
    <template v-slot:content>
      <div class="text-xs">
        <p class="text-gray-600 leading-3 font-semibold mb-2">{{ __('Accepts:') }}</p>
        <ul class="text-gray-500 ml-1">
          <li class="mb-1">
            <a
              class="text-blue-500 underline"
              href="https://podlove.org/simple-chapters/"
              target="_blank"
              >{{ __('Podlove Simple Chapters') }}</a
            >
            (<code>.psc</code>),
          </li>
          <li class="mb-1">
            <a class="text-blue-500 underline" href="http://www.audacityteam.org" target="_blank"
              >{{ __('Audacity') }}</a
            >
            {{ __('Track Labels') }},
          </li>
          <li class="mb-1">
            <a class="text-blue-500 underline" href="https://hindenburg.com" target="_blank"
              >{{ __('Hindenburg') }}</a
            >
            {{ __('project files and MP4Chaps') }} (<code>.txt</code>)
          </li>
        </ul>
      </div>
    </template>
  </tooltip>
</template>

<script lang="ts">
import { get } from 'lodash'
import { injectStore } from 'redux-vuex'
import { parse as parseChapters } from '@store/chapters.store'
import { defineComponent } from '@vue/runtime-core'

import PodloveButton from '@components/button/Button.vue'
import Tooltip from '@components/tooltip/Tooltip.vue'

export default defineComponent({
  components: {
    PodloveButton,
    Tooltip,
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
