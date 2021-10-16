<template>
  <module name="chapters" title="Chapter Marks">
    <tabs-container>
      <tab name="chapters" title="Chapters">
        <chapters-form></chapters-form>
      </tab>
      <tab name="import" title="Import">Import Tab</tab>
      <tab name="export" title="Export">Export Tab</tab>
    </tabs-container>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { TabsContainer, Tab } from '@components/tabs'
import Module from '@components/module'

import ChaptersForm from './ChaptersForm.vue';

export default defineComponent({
  components: { Module, TabsContainer, Tab, ChaptersForm }
})
</script>

<style lang="postcss">
</style>
