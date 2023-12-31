<template>
  <module name="license" :title="__('License')">
    <template v-slot:actions>
      <LicenseSelector></LicenseSelector>
    </template>
    <div class="p-3">
      <div class="mb-3">
        <LicenseName></LicenseName>
      </div>
      <div class="mb-3">
        <LicenseUrl></LicenseUrl>
      </div>
    </div>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

import Module from '@components/module/Module.vue'

import LicenseSelector from './components/LicenseSelector.vue'
import LicenseName from './components/LicenseName.vue'
import LicenseUrl from './components/LicenseUrl.vue'
import LicensePreview from './components/LicenseView.vue'

export default defineComponent({
  components: {
    Module,
    LicenseSelector,
    LicenseName,
    LicenseUrl,
    LicensePreview
  },
})
</script>

<style></style>
