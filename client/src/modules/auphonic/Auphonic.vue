<template>
  <module name="auphonic" title="Auphonic">
    <div class="m-3 flex flex-col gap-3">
      <div>token: {{ token }}</div>
      <div>production id: {{ productionId }}</div>
      <div>
        <podlove-button variant="primary"
          ><plus-sm-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> Create
          Production</podlove-button
        >
      </div>
      <div>or</div>
      <div>
        <podlove-button variant="primary"
          ><plus-sm-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> Create Multitrack
          Production</podlove-button
        >
      </div>
      <div>or</div>
      <div>
        <em>select existing production</em>
      </div>
    </div>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import Module from '@components/module/Module.vue'
import PodloveButton from '@components/button/Button.vue'
import { PlusSmIcon } from '@heroicons/vue/outline'
import { selectors } from '@store'

import { injectStore, mapState } from 'redux-vuex'
import * as auphonic from '@store/auphonic.store'

export default defineComponent({
  components: {
    Module,
    PodloveButton,
    PlusSmIcon,
  },

  setup() {
    return {
      state: mapState({
        token: selectors.auphonic.token,
        productionId: selectors.auphonic.productionId,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  computed: {
    token(): string {
      return this.state.token || ''
    },
    productionId(): string {
      return this.state.productionId || ''
    },
  },

  created() {
    this.dispatch(auphonic.init())
    // this.dispatch(auphonic.setProduction('proof of concept, not a real production id'))
  },
})
</script>
