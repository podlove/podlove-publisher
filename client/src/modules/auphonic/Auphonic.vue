<template>
  <module name="auphonic" title="Auphonic">
    <div class="m-12 mb-24 text-center">
      <AuphonicLogo className="mx-auto h-16 w-16 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No production connected yet</h3>
      <p class="mt-1 text-sm text-gray-500">Manage your audio post production with Auphonic.</p>
      <div class="mt-8 flex justify-center align-middle content-center items-center gap-3">
        <div class="w-full max-w-md">
          <SelectProduction />
        </div>
      </div>
      <div class="mt-8 flex justify-center align-middle content-center items-center gap-3">
        <div class="w-full max-w-md">
          <SelectPreset />
        </div>
      </div>
      <div class="mt-10 flex justify-center align-middle content-center items-center gap-3">
        <podlove-button variant="primary" @click="createProduction"
          ><plus-sm-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> Create
          Production</podlove-button
        >
        <div class="text-gray-400">or</div>
        <podlove-button variant="primary" @click="createMultitrackProduction"
          ><plus-sm-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> Create Multitrack
          Production</podlove-button
        >
      </div>
    </div>

    <hr />

    <div class="m-7" v-if="productionId">
      <ManageProductionForm />
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
import SelectPreset from './components/SelectPreset.vue'
import ManageProductionForm from './components/ManageProductionForm.vue'
import AuphonicLogo from './components/Logo.vue'

export default defineComponent({
  components: {
    Module,
    PodloveButton,
    PlusSmIcon,
    SelectProduction,
    SelectPreset,
    ManageProductionForm,
    AuphonicLogo,
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
