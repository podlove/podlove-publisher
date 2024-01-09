<template>
  <div class="border-gray-200 border-b pb-2 px-3 py-5">
    <h3 class="text-lg leading-6 font-medium text-gray-900">{{ __('License Selector') }}</h3>
  </div>
  <div>
    <div class="mb-3">
      <label class="block text-sm font-medium text-gray-700">
        Version:
      </label>
      <select :value="getLicenseData().version" @input="updateVersion($event)" class="
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
          ">
        <option v-for="(version, vindex) in PodloveLicenseVersionList" :value="version.value" :key="`version-${vindex}`">
          {{ version.value }}
        </option>
      </select>
    </div>
    <div v-if="isCommercialNModificationNeeded" class="mb-3">
      <label class="block text-sm font-medium text-gray-700">
        Allow modifications of your work?
      </label>
      <select :value="getLicenseData().optionModification" @input="updateModification($event)" class="
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
          ">
        <option v-for="(modification, mindex) in PodloveLicenseOptionModificationList" :value="modification.value"
          :key="`modification-${mindex}`">
          {{ modification.value }}
        </option>
      </select>
    </div>
    <div v-if="isCommercialNModificationNeeded" class="mb-3">
      <label class="block text-sm font-medium text-gray-700">
        Allow commercial uses of your work?
      </label>
      <select :value="getLicenseData().optionCommercial" @input="updateCommercial($event)" class="
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
          ">
        <option v-for="(commercial, cindex) in PodloveLicenseOptionCommercialList" :value="commercial.value"
          :key="`commercial-${cindex}`">
          {{ commercial.value }}
        </option>
      </select>
    </div>
    <div v-if="isJurisdicationNeeded" class="mb-3">
      <label class="block text-sm font-medium text-gray-700">
        License Jurisdiction
      </label>
      <select :value="getLicenseData().optionJurisdication?.name" @input="updateJurisdication($event)" class="
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
          ">
        <option v-for="(jurisdication, jindex) in PodloveLicenseOptionJurisdication" :value="jurisdication.name"
          :key="`jurisdiction-${jindex}`">
          {{ jurisdication.name }}
        </option>
      </select>
    </div>
    <div class="mb-5">
      <LicensePreview :license-data="getLicenseData()"></LicensePreview>
    </div>
  </div>
</template>

<script lang="ts">
import { PropType, defineComponent } from '@vue/runtime-core'
import { injectStore, mapState } from 'redux-vuex'

import { update as updateEpisode } from '@store/episode.store'
import { update as updatePodcast } from '@store/podcast.store'
import { selectors } from '@store'

import LicensePreview from './LicenseView.vue'
import PodloveButton from '@components/button/Button.vue'
import Modal from '@components/modal/Modal.vue'

import { PodloveLicense, PodloveLicenseVersion, PodloveLicenseOptionCommercial, 
  PodloveLicenseOptionModification, PodloveLicenseOptionJurisdication, PodloveLicenseScope } from '../../../types/license.types'
import { getLicenseFromUrl, getLicenseUrl } from '@lib/license'
import episodeSagas from '@sagas/episode.sagas'

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
  data() {
    return {
      PodloveLicenseVersionList,
      PodloveLicenseOptionCommercialList,
      PodloveLicenseOptionModificationList,
      PodloveLicenseOptionJurisdication
    }
  },
  setup() {
    return {
      state: mapState({
        episode_license_url: selectors.episode.license_url,
        podcast_license_url: selectors.podcast.license_url
      }),
      dispatch: injectStore().dispatch,
    }
  },
  props: {
    scope: {
      type: String as PropType<PodloveLicenseScope>,
      default: PodloveLicenseScope.Episode
    }
  },
  computed: {
    isCommercialNModificationNeeded() : boolean {
      if (this.getLicenseData().type == "cc" && (this.getLicenseData().version == PodloveLicenseVersion.cc3 || this.getLicenseData().version == PodloveLicenseVersion.cc4))
        return true
      return false
    },
    isJurisdicationNeeded() : boolean {
      if (this.getLicenseData().type == "cc" && this.getLicenseData().version == PodloveLicenseVersion.cc3)
        return true;
      return false;
    },
  },
  methods: {
    updateVersion(event: Event) {
      let licenseData = this.getLicenseData()
      licenseData.type = "cc"
      const value : string = (event.target as HTMLInputElement).value
      switch (value) {
        case PodloveLicenseVersion.cc0:
          licenseData.version = PodloveLicenseVersion.cc0
          break;
        case PodloveLicenseVersion.pdmark:
          licenseData.version = PodloveLicenseVersion.pdmark
          break;
        case PodloveLicenseVersion.cc3:
          licenseData.version = PodloveLicenseVersion.cc3
          break;
        case PodloveLicenseVersion.cc4:
          licenseData.version = PodloveLicenseVersion.cc4
          break;
      }
      this.updateLicenseDataNameNUrl(licenseData)
    },
    updateCommercial(event: Event) {
      let licenseData = this.getLicenseData()
      const value : string = (event.target as HTMLInputElement).value
      switch (value) {
        case PodloveLicenseOptionCommercial.yes:
          licenseData.optionCommercial = PodloveLicenseOptionCommercial.yes
          break;
        case PodloveLicenseOptionCommercial.no:
          licenseData.optionCommercial = PodloveLicenseOptionCommercial.no
          break;
      }
      this.updateLicenseDataUrl(licenseData)
    },
    updateModification(event: Event) {
      let licenseData = this.getLicenseData()
      const value : string = (event.target as HTMLInputElement).value
      switch (value) {
        case PodloveLicenseOptionModification.yes:
          licenseData.optionModification = PodloveLicenseOptionModification.yes
          break;
          case PodloveLicenseOptionModification.yesbutshare:
          licenseData.optionModification = PodloveLicenseOptionModification.yesbutshare
          break;
        case PodloveLicenseOptionModification.no:
          licenseData.optionModification = PodloveLicenseOptionModification.no
          break;
      }
      this.updateLicenseDataUrl(licenseData)
    },
    updateJurisdication(event: Event) {
      let licenseData = this.getLicenseData()
      const value : string = (event.target as HTMLInputElement).value
      const idx: number = PodloveLicenseOptionJurisdication.findIndex(item => item.name === value)

      if (idx != undefined) {
        licenseData.optionJurisdication = PodloveLicenseOptionJurisdication[idx]
        this.updateLicenseDataUrl(licenseData)
      }
    },
    updateLicenseDataUrl(licenseData: PodloveLicense) {
      if (this.scope == PodloveLicenseScope.Episode) {
          this.dispatch(
            updateEpisode({ prop: 'license_url', value: getLicenseUrl(licenseData) })
          )
        }
        if (this.scope == PodloveLicenseScope.Podcast) {
          this.dispatch(
            updatePodcast({ prop: 'license_url', value: getLicenseUrl(licenseData) })
          )
        }
    },
    updateLicenseDataNameNUrl(licenseData: PodloveLicense) {
      if (this.scope == PodloveLicenseScope.Episode) {
          this.dispatch(
            updateEpisode({ prop: 'license_name', value: licenseData.version })
          )
          this.dispatch(
            updateEpisode({ prop: 'license_url', value: getLicenseUrl(licenseData) })
          )
        }
        if (this.scope == PodloveLicenseScope.Podcast) {
          this.dispatch(
            updatePodcast({ prop: 'license_name', value: licenseData.version })
          )
          this.dispatch(
            updatePodcast({ prop: 'license_url', value: getLicenseUrl(licenseData) })
          )
        }
    },
    getLicenseData() : PodloveLicense {
      if (this.scope == PodloveLicenseScope.Episode) {
        if (this.state.episode_license_url == null || this.state.episode_license_url == undefined) {
          return {
            type: "cc",
            version: PodloveLicenseVersion.pdmark,
            optionCommercial: null,
            optionModification: null,
            optionJurisdication: null
          } as PodloveLicense
        }
        return getLicenseFromUrl(this.state.episode_license_url)
      }
      if (this.state.podcast_license_url == null || this.state.podcast_license_url == undefined) {
        return {
          type: "cc",
          version: PodloveLicenseVersion.pdmark,
          optionCommercial: null,
          optionModification: null,
          optionJurisdication: null
        } as PodloveLicense
      }
      return getLicenseFromUrl(this.state.podcast_license_url)
    }
  },
})

</script>

<style lang="postcss">
</style>