<template>
  <div class="mt-3">
    <div class="mb-3 text-sm font-medium text-gray-700">
      License preview:
    </div>
    <div v-if="isImageAvailable">
      <div>
        <div class="mb-3 w-full">
        <div class="flex justify-center items-center">
          <img class="text-center" :src="`${imageUrl}`"/>
        </div>
      </div>
      <div class="mb-3 w-full text-center">
        <p class="text-sm font-medium text-gray-700">This work is licensend under </p>
        <a class="text-sm font-medium text-gray-700"
          :href="`${licenseUrl}`">{{ licenseUrl }}</a>
      </div>
    </div>
    <div v-if="!isImageAvailable">
      <p class="text-sm font-medium text-gray-700">No license selected!</p>
    </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { injectStore, mapState } from 'redux-vuex'

import { selectors } from '@store'

import Module from '@components/module/Module.vue'
import { PodloveLicense, PodloveLicenseVersion } from '../../../types/license.types'
import { getImageUrl, getLicenseUrl } from '@lib/license'

export default defineComponent({
  components: {
    Module,
  },

  props: {
    licenseData: {
      type: null,
      default: { 
        type: "cc",
        version: PodloveLicenseVersion.pdmark,
        optionCommercial: null,
        optionModification: null,
        optionJurisdication: null
      } as PodloveLicense
    }
  },
  setup() {
    return {
      state: mapState({
        baseUrl: selectors.runtime.baseUrl,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  computed: {
    licenseUrl() : string | null {
      return getLicenseUrl(this.licenseData)
    },
    imageUrl() : string | null {
      return getImageUrl(this.licenseData, this.state.baseUrl)
    },
    isImageAvailable() : boolean {
      if (getImageUrl(this.licenseData, this.state.baseUrl) !== null)
        return true
      return false
    }
  }

})
</script>



<style>
</style>