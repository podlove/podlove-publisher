<template>
  <module name="mediafiles" title="Media Files"
    >Hello Media File World{{ !isInitializing && '!' }}</module
  >
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { selectors } from '@store'

import { injectStore, mapState } from 'redux-vuex'
import * as mediafiles from '@store/mediafiles.store'
import Module from '@components/module/Module.vue'

export default defineComponent({
  components: {
    Module,
  },

  setup() {
    return {
      state: mapState({
        isInitializing: selectors.mediafiles.isInitializing,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  computed: {
    isInitializing(): boolean {
      return this.state.isInitializing
    },
  },

  created() {
    this.dispatch(mediafiles.init())
  },
})
</script>
