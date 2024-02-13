<template>
  <module name="license" :title=getModuleTitle>
    <template v-slot:actions>
      <div v-if="isScopeEpisode">
        <LicenseSelectorButton :scope="scope"></LicenseSelectorButton>
      </div>
    </template>
    <div class="p-3">
      <div class="mb-3">
        <LicenseName :scope="scope"></LicenseName>
      </div>
      <div class="mb-3">
        <LicenseUrl :scope="scope"></LicenseUrl>
      </div>
      <div v-if="!isScopeEpisode">
        <LicenseSelector :scope="scope"></LicenseSelector>
      </div>
    </div>
  </module>
</template>

<script lang="ts">
import { PropType, defineComponent } from 'vue'

import Module from '@components/module/Module.vue'

import LicenseName from './components/LicenseName.vue'
import LicenseUrl from './components/LicenseUrl.vue'
import LicenseSelector from './components/LicenseSelector.vue'
import LicenseSelectorButton from './components/LicenseSelectorButton.vue'

import { PodloveLicenseScope } from '../../types/license.types'

export default defineComponent({
  components: {
    Module,
    LicenseName,
    LicenseUrl,
    LicenseSelector,
    LicenseSelectorButton
  },
  props: {
    scope: {
      type: String as PropType<PodloveLicenseScope>,
      default: PodloveLicenseScope.Episode
    }
  },
  computed: {
    isScopeEpisode(): boolean {
      return this.scope === PodloveLicenseScope.Episode ? true : false
    },
    getModuleTitle(): string {
      return this.scope === PodloveLicenseScope.Episode ? "Episode License" : "Podcast License"
    }
  }
})
</script>

<style></style>
