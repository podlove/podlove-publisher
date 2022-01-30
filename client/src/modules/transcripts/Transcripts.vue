<template>
  <module name="transcript" title="Transcripts">
    <template v-slot:actions>
      <transcripts-voices class="mr-1" />
      <transcripts-export class="mr-1" />
      <transcripts-delete />
    </template>
    <transcripts-list />

    <!-- <tabs-container>
      <tab name="transcript" title="Transcript Content">
        <transcripts-list />
      </tab>
      <tab name="voices" title="Voices">

      </tab>
      <tab name="import" title="Import">
      </tab>
      <tab name="export" title="Export">
      </tab>
      <tab name="delete" title="Delete">
      </tab>
    </tabs-container> -->
  </module>
</template>
<script lang="ts">
import { defineComponent } from 'vue'
import { injectStore } from 'redux-vuex'
import { TabsContainer, Tab } from '@components/tabs'

import Module from '@components/module/Module.vue'
import * as transcripts from '@store/transcripts.store'
import * as contributors from '@store/contributors.store'

import TranscriptsList from './components/List.vue'
import TranscriptsVoices from './components/Voices.vue'
import TranscriptsExport from './components/Export.vue'
import TranscriptsDelete from './components/Delete.vue'

export default defineComponent({
  components: {
    Module,
    TabsContainer,
    Tab,
    TranscriptsList,
    TranscriptsVoices,
    TranscriptsExport,
    TranscriptsDelete,
  },

  setup(): { dispatch: Function } {
    return {
      dispatch: injectStore().dispatch,
    }
  },

  created() {
    this.dispatch(contributors.init())
    this.dispatch(transcripts.init())
  },
})
</script>

<style></style>
