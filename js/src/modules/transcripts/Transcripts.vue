<template>
  <module name="transcript" title="Transcripts">
    <tabs-container>
      <tab name="transcript" title="Transcript Content">
        <div>Transcript Content</div>
      </tab>
      <tab name="voices" title="Voices">
        <div>Voices Tab</div>
      </tab>
      <tab name="import" title="Import">
        <div>Import Tab</div>
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

export default defineComponent({
  components: { Module, TabsContainer, Tab }
})
</script>

<style></style>
