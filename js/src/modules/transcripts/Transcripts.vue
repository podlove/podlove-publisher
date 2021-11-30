<template>
  <module name="transcript" title="Transcripts">
    <tabs-container>
      <tab name="transcript" title="Transcript Content">
        <transcripts-list />
      </tab>
      <tab name="voices" title="Voices">
        <transcripts-voices />
      </tab>
      <tab name="import" title="Import">
        <transcripts-import />
      </tab>
      <tab name="export" title="Export">
        <div>Export Tab</div>
      </tab>
      <tab name="delete" title="Delete">
        <div>Delete Tab</div>
      </tab>
    </tabs-container>
  </module>

</template>
<script lang="ts">
import { defineComponent } from 'vue'
import { TabsContainer, Tab } from '@components/tabs'
import Module from '@components/module'

import TranscriptsList from './components/List.vue'
import TranscriptsVoices from './components/Voices.vue'
import TranscriptsImport from './components/Import.vue'

export default defineComponent({
  components: { Module, TabsContainer, Tab, TranscriptsList, TranscriptsVoices, TranscriptsImport }
})
</script>

<style></style>
