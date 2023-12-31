<template>
  <podlove-button variant="secondary" size="small" @click="openSelector">{{ __('License Selector') }}</podlove-button>
  <modal :open="openDialog" @close="closeSelector">
    <div class="border-gray-200 border-b pb-2 px-4 -mx-6 mb-4">
      <h3 class="text-lg leading-6 font-medium text-gray-900">{{ __('License selector') }}</h3>
    </div>
    <div>
      <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700">
          Version:
        </label>
        <select
          :value="licenseData.version"
          @input="updateVersion($event)"
          class="
            mt-1
            block
            w-full
            py-2
            px-3
            border border-gray-300
            bg-white
            rounded-md
            shadow-sm
            focus:outline-none focus:ring-indigo-500 focus:border-indigo-500
            sm:text-sm
          "
        >
          <option
            v-for="(version, vindex) in PodloveLicenseVersionList"
            :value="version.value"
            :key="`version-${vindex}`"
          >
            {{ version.value }}
          </option>
        </select>
      </div>
      <div v-if="isCommercialNModificationNeeded" class="mb-3">
        <label class="block text-sm font-medium text-gray-700">
          Allow modifications of your work?
        </label>
        <select
          :value="licenseData.optionModification"
          @input="updateModification($event)"
          class="
            mt-1
            block
            w-full
            py-2
            px-3
            border border-gray-300
            bg-white
            rounded-md
            shadow-sm
            focus:outline-none focus:ring-indigo-500 focus:border-indigo-500
            sm:text-sm
          "
        >
          <option
            v-for="(modification, mindex) in PodloveLicenseOptionModificationList"
            :value="modification.value"
            :key="`modification-${mindex}`"
          >
            {{ modification.value }}
          </option>
        </select>
      </div>
      <div v-if="isCommercialNModificationNeeded" class="mb-3">
        <label class="block text-sm font-medium text-gray-700">
          Allow commercial uses of your work?
        </label>
        <select
          :value="licenseData?.optionCommercial"
          @input="updateCommercial($event)"
          class="
            mt-1
            block
            w-full
            py-2
            px-3
            border border-gray-300
            bg-white
            rounded-md
            shadow-sm
            focus:outline-none focus:ring-indigo-500 focus:border-indigo-500
            sm:text-sm
          "
        >
          <option
            v-for="(commercial, cindex) in PodloveLicenseOptionCommercialList"
            :value="commercial.value"
            :key="`commercial-${cindex}`"
          >
            {{ commercial.value }}
          </option>
        </select>
      </div>
      <div class="mb-5">
        <LicensePreview :license-data="licenseData"></LicensePreview>
      </div>
    </div>
  </modal>
</template>

<script lang="ts">
import { defineComponent } from '@vue/runtime-core'
import { injectStore, mapState } from 'redux-vuex'

import { update as updateEpisode } from '@store/episode.store'
import { selectors } from '@store'

import LicensePreview from './LicenseView.vue'
import PodloveButton from '@components/button/Button.vue'
import Modal from '@components/modal/Modal.vue'

import { PodloveLicense, PodloveLicenseVersion, PodloveLicenseOptionCommercial, PodloveLicenseOptionModification } from '../../../types/license.types'
import { getLicenseFromUrl, getLicenseUrl } from '@lib/license'

const PodloveLicenseVersionList: {
  key: string;
  value: string;
}[] = Object.entries(PodloveLicenseVersion).map(([key, value]) => ({ key, value }));

const PodloveLicenseOptionCommercialList: {
  key: string;
  value: string;
}[] = Object.entries(PodloveLicenseOptionCommercial).map(([key, value]) => ({ key, value }));

const PodloveLicenseOptionModificationList: {
  key: string;
  value: string;
}[] = Object.entries(PodloveLicenseOptionModification).map(([key, value]) => ({ key, value }));

export default defineComponent({
  components: { 
    PodloveButton,
    Modal,
    LicensePreview,
  },
  setup() {
    return {
      state: mapState({
        license_url: selectors.episode.license_url
      }),
      dispatch: injectStore().dispatch,
    }
  },
  data() {
    return {
      openDialog: false,
      licenseData: { } as PodloveLicense,
      PodloveLicenseVersionList,
      PodloveLicenseOptionCommercialList,
      PodloveLicenseOptionModificationList
    }
  },
  computed: {
    isCommercialNModificationNeeded() : boolean {
      if (this.licenseData.type == "cc" && (this.licenseData.version == PodloveLicenseVersion.cc3 || this.licenseData.version == PodloveLicenseVersion.cc4))
        return true
      return false
    },
  },
  methods: {
    openSelector() {
      if (this.state.license_url != null && this.state.license_url != undefined) {
        this.licenseData = getLicenseFromUrl(this.state.license_url)
      } 
      this.openDialog = true
    },
    closeSelector() {
      this.openDialog = false
    },
    updateVersion(event: Event) {
      this.licenseData.type = "cc"
      const value : string = (event.target as HTMLInputElement).value
      switch (value) {
        case PodloveLicenseVersion.cc0:
          this.licenseData.version = PodloveLicenseVersion.cc0
          break;
        case PodloveLicenseVersion.pdmark:
          this.licenseData.version = PodloveLicenseVersion.pdmark
          break;
        case PodloveLicenseVersion.cc3:
          this.licenseData.version = PodloveLicenseVersion.cc3
          break;
        case PodloveLicenseVersion.cc4:
          this.licenseData.version = PodloveLicenseVersion.cc4
          break;
      }
      this.dispatch(
        updateEpisode({ prop: 'license_name', value: this.licenseData.version })
      )
      this.dispatch(
        updateEpisode({ prop: 'license_url', value: getLicenseUrl(this.licenseData) })
      )
    },
    updateCommercial(event: Event) {
      const value : string = (event.target as HTMLInputElement).value
      switch (value) {
        case PodloveLicenseOptionCommercial.yes:
          this.licenseData.optionCommercial = PodloveLicenseOptionCommercial.yes
          break;
        case PodloveLicenseOptionCommercial.no:
          this.licenseData.optionCommercial = PodloveLicenseOptionCommercial.no
          break;
      }
      this.dispatch(
        updateEpisode({ prop: 'license_url', value: getLicenseUrl(this.licenseData) })
      )
    },
    updateModification(event: Event) {
      const value : string = (event.target as HTMLInputElement).value
      switch (value) {
        case PodloveLicenseOptionModification.yes:
          this.licenseData.optionModification = PodloveLicenseOptionModification.yes
          break;
          case PodloveLicenseOptionModification.yesbutshare:
          this.licenseData.optionModification = PodloveLicenseOptionModification.yesbutshare
          break;
        case PodloveLicenseOptionModification.no:
          this.licenseData.optionModification = PodloveLicenseOptionModification.no
          break;
      }
      this.dispatch(
        updateEpisode({ prop: 'license_url', value: getLicenseUrl(this.licenseData) })
      )
    }
  },
  mounted() {
    this.licenseData = {
      type: null,
      version: null,
      optionCommercial: null,
      optionModification: null,
      optionJurisdication: null,
    } as PodloveLicense
  },
})

</script>

<style lang="postcss">
</style>