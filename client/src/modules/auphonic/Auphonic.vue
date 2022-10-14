<template>
  <module class="font-sans" name="auphonic" title="Auphonic">
    <div v-if="!isProductionSelected">
      <StartScreen />
    </div>

    <div class="m-7" v-if="productionId">
      <ManageProductionForm />
    </div>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { selectors } from '@store'

import { injectStore, mapState } from 'redux-vuex'
import * as auphonic from '@store/auphonic.store'
import ManageProductionForm from './components/ManageProductionForm.vue'
import StartScreen from './components/StartScreen.vue'
import AuphonicLogo from './components/Logo.vue'

export default defineComponent({
  components: {
    ManageProductionForm,
    StartScreen,
    AuphonicLogo,
  },

  setup() {
    return {
      state: mapState({
        productionId: selectors.auphonic.productionId,
        isInitializing: selectors.auphonic.isInitializing,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  computed: {
    productionId(): string {
      return this.state.productionId || ''
    },
    isInitializing(): boolean {
      return this.state.isInitializing
    },
    isProductionSelected(): boolean {
      return !!this.productionId
    },
  },

  created() {
    this.dispatch(auphonic.init())
  },
})
</script>
