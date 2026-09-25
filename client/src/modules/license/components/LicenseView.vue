<template>
  <div class="mt-3">
    <div class="mb-3 text-sm font-medium text-gray-700">
      {{ __('License preview:', 'podlove-podcasting-plugin-for-wordpress') }}
    </div>
    <div v-if="isImageAvailable">
      <div>
        <div class="mb-3 w-full">
        <div class="flex justify-center items-center">
          <img class="text-center" :src="`${imageUrl}`"/>
        </div>
      </div>
      <div class="mb-3 w-full text-center">
        <p class="text-sm font-medium text-gray-700">
          <template v-for="(part, index) in licenseSentence" :key="index">
            {{ part }}<a v-if="index === 0" :href="licenseUrl || undefined">{{ licenseUrl }}</a>
          </template>
        </p>
      </div>
    </div>
    <div v-if="!isImageAvailable">
      <p class="text-sm font-medium text-gray-700">{{ __('No license selected!', 'podlove-podcasting-plugin-for-wordpress') }}</p>
    </div>
    </div>
  </div>
</template>

<script lang="ts">
import { __ } from '../../../plugins/translations'
import { defineComponent } from 'vue'
import { selectors } from '@store'
import { injectAppDispatch, mapAppState } from '@store/vue'

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
      state: mapAppState({
        baseUrl: selectors.runtime.baseUrl,
      }),
      dispatch: injectAppDispatch(),
    }
  },

  computed: {
    licenseSentence(): string[] {
      return __('This work is licensed under %s.', 'podlove-podcasting-plugin-for-wordpress').split('%s')
    },
    licenseUrl() : string | null {
      return getLicenseUrl(this.licenseData)
    },
    imageUrl() : string | null {
      return getImageUrl(this.licenseData, this.state.baseUrl || '')
    },
    isImageAvailable() : boolean {
      if (getImageUrl(this.licenseData, this.state.baseUrl || '') !== null)
        return true
      return false
    }
  }

})
</script>



<style>
</style>
