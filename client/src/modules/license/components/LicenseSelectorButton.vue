<template>
  <podlove-button variant="secondary" size="small" @click="openSelector">{{ __('License Selector', 'podlove-podcasting-plugin-for-wordpress') }}</podlove-button>
  <modal :open="openDialog" @close="closeSelector">
    <LicenseSelector :scope="scope"></LicenseSelector>
  </modal>

</template>

<script lang="ts">
import { PropType, defineComponent } from '@vue/runtime-core'

import PodloveButton from '@components/button/Button.vue'
import Modal from '@components/modal/Modal.vue'

import LicenseSelector from './LicenseSelector.vue'
import { PodloveLicenseScope } from '../../../types/license.types';

export default defineComponent({
  components: {
    PodloveButton,
    Modal,
    LicenseSelector,
  },
  props: {
    scope: {
      type: String as PropType<PodloveLicenseScope>,
      default: PodloveLicenseScope.Episode
    }
  },
  data() {
    return {
      openDialog: false
    }
  },
  methods: {
    openSelector() {
      this.openDialog = true
    },
    closeSelector() {
      this.openDialog = false
    },
  }
})

</script>

<style>
</style>
