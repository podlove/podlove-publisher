<template>
  <module name="chapters" title="Chapter Marks">
    <template v-slot:actions>
      <chapters-import class="mr-1"></chapters-import>
      <chapters-export></chapters-export>
    </template>
    <chapters-form></chapters-form>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { TabsContainer, Tab } from '@components/tabs'
import Module from '@components/module'

import ChaptersForm from './components/Form.vue'
import ChaptersImport from './components/Import.vue'
import ChaptersExport from './components/Export.vue'

export default defineComponent({
  components: {
    Module,
    TabsContainer,
    Tab,
    ChaptersForm,
    ChaptersImport,
    ChaptersExport
  },
})
</script>

<style lang="postcss">
</style>
