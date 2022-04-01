<template>
  <module name="auphonic" title="Auphonic">
    <div class="m-7 flex flex-col gap-7">
      <div>production id: {{ productionId }}</div>
      <div class="max-w-md">
        <SelectProduction />
      </div>
      <div>or</div>
      <div>
        <podlove-button variant="primary" @click="createProduction"
          ><plus-sm-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> Create
          Production</podlove-button
        >
      </div>
      <div>or</div>
      <div>
        <podlove-button variant="primary" @click="createMultitrackProduction"
          ><plus-sm-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> Create Multitrack
          Production</podlove-button
        >
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
import SelectProduction from './components/SelectProduction.vue'

export default defineComponent({
  components: {
    Module,
    PodloveButton,
    PlusSmIcon,
    SelectProduction,
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

  methods: {
    createProduction() {
      this.dispatch(auphonic.createProduction())
    },
    createMultitrackProduction() {
      this.dispatch(auphonic.createMultitrackProduction())
    },
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
  },
})
</script>
