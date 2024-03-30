<template>
  <module name="chapters" :title="__('Chapter Marks', 'podlove-podcasting-plugin-for-wordpress')">
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
import Module from '@components/module/Module.vue'

import { injectStore } from 'redux-vuex'

import * as chapters from '@store/chapters.store'

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

  setup(): { dispatch: Function } {
    return {
      dispatch: injectStore().dispatch,
    }
  },

  created() {
    this.dispatch(chapters.init())
  }
})
</script>

<style lang="postcss">
</style>
