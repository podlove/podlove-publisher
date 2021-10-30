<template>
  <module name="chapters" title="Chapter Marks">
    <tabs-container>
      <tab name="chapters" title="Chapters">
        <chapters-form></chapters-form>
      </tab>
      <tab name="import" title="Import">
        <chapters-import></chapters-import>
      </tab>
      <tab name="export" title="Export">
        <chapters-export></chapters-export>
      </tab>
    </tabs-container>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { TabsContainer, Tab } from '@components/tabs'
import Module from '@components/module'

import ChaptersForm from './components/Form.vue';
import ChaptersImport from './components/Import.vue';
import ChaptersExport from './components/Export.vue';

export default defineComponent({
  components: { Module, TabsContainer, Tab, ChaptersForm, ChaptersImport, ChaptersExport }
})
</script>

<style lang="postcss">
</style>
