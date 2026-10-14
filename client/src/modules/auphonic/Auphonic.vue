<template>
  <module name="auphonic" title="Auphonic">
    <p v-if="state.initializationError" class="p-7 text-red-700" role="alert">
      {{ state.initializationError }}
    </p>
    <template v-else>
      <div v-if="!isProductionSelected">
        <StartScreen />
      </div>

      <div class="m-7" v-if="productionId">
        <ManageProductionForm />
      </div>
    </template>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { selectors } from '@store'
import * as auphonic from '@store/auphonic.store'
import ManageProductionForm from './components/ManageProductionForm.vue'
import StartScreen from './components/StartScreen.vue'
import AuphonicLogo from './components/Logo.vue'
import Module from '@components/module/Module.vue'
import { injectAppDispatch, mapAppState } from '@store/vue'

export default defineComponent({
  components: {
    Module,
    ManageProductionForm,
    StartScreen,
    AuphonicLogo,
  },

  setup() {
    return {
      state: mapAppState({
        productionId: selectors.auphonic.productionId,
        isInitializing: selectors.auphonic.isInitializing,
        initializationError: selectors.auphonic.initializationError,
      }),
      dispatch: injectAppDispatch(),
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
