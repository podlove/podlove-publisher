<template>
  <module name="contributors" title="Contributors">
    <contributors-form></contributors-form>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { TabsContainer, Tab } from '@components/tabs'
import Module from '@components/module/Module.vue'

import { injectStore } from 'redux-vuex'

import * as contributors from '@store/contributors.store'

import ContributorsForm from './components/Form.vue'

export default defineComponent({
  components: {
    Module,
    TabsContainer,
    Tab,
    ContributorsForm,
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
