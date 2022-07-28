<template>
  <module name="contributors" title="Contributors">
    <!-- <template v-slot:actions>
      <chapters-import class="mr-1"></chapters-import>
      <chapters-export></chapters-export>
    </template>
    <chapters-form></chapters-form> -->
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { TabsContainer, Tab } from '@components/tabs'
import Module from '@components/module/Module.vue'

import { injectStore } from 'redux-vuex'

import * as contributors from '@store/contributors.store'

export default defineComponent({
  components: {
    Module,
    TabsContainer,
    Tab,
  },

  setup(): { dispatch: Function } {
    return {
      dispatch: injectStore().dispatch,
    }
  },

  created() {
    this.dispatch(contributors.init())
  }
})
</script>

<style lang="postcss">
</style>
