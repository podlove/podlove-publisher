<template>
  <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
    <div class="mb-6">
      <h2 class="mb-2 text-xl font-medium text-gray-700">Manage Features</h2>
      <p class="text-sm text-gray-600">
        Enable or disable PLUS features. Changes will take effect immediately.
      </p>
    </div>

    <div class="space-y-3">
      <Feature
        title="File Storage"
        :modelValue="features.fileStorage"
        @update:modelValue="handleFeatureToggle('fileStorage')"
      >
        <p class="text-sm text-gray-600 mb-2">
          Store your podcast files in fast and reliable cloud storage. Don't worry about dealing
          with WordPress performance issues as your podcast grows. Focus on creating great content
          and let us handle the rest.
        </p>

        <p class="text-sm text-gray-600 mb-2">
          You can enable file storage by using the feature switch here. This will automatically upload
          your podcast files to the cloud and make them available for download.
        </p>

        <p class="text-sm text-gray-600">
          If at any point you want to disable file storage, you can do so by disabling the feature
          here. Then files will be served from your local WordPress or FTP storage again, as you
          configure it here in the plugin.
        </p>

        <template #footer v-if="features.fileStorage && needsMigration">
          <PlusFileMigration />
        </template>
      </Feature>

      <Feature
        title="Feed Proxy"
        :modelValue="features.feedProxy"
        @update:modelValue="handleFeatureToggle('feedProxy')"
      >
        <p class="text-sm text-gray-600">
          When Feed Proxy is enabled, all feed requests are automatically redirected to the
          corresponding proxy feed URL. It can be disabled at any time without risk of losing
          subscribers because a temporary redirect (HTTP 307) is used.
        </p>
      </Feature>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import Feature from './Feature.vue'
import PlusFileMigration from '../plus_file_migration/PlusFileMigration.vue'
import * as plusFileMigration from '@store/plusFileMigration.store'
import { injectStore, mapState } from 'redux-vuex'
import * as plus from '@store/plus.store'
import { selectors } from '@store'
import type { PlusFeatures } from '@store/plus.store'

export default defineComponent({
  components: {
    Feature,
    PlusFileMigration,
  },

  setup() {
    return {
      state: mapState({
        features: selectors.plus.features,
        files: selectors.plusFileMigration.episodesWithFiles,
        isMigrationComplete: selectors.plusFileMigration.isMigrationComplete,
      }),
      dispatch: injectStore().dispatch,
    }
  },
  created() {
    this.dispatch(plus.init())
    this.dispatch(plusFileMigration.init())
  },

  methods: {
    handleFeatureToggle(featureKey: keyof PlusFeatures) {
      this.dispatch(plus.setFeature({ feature: featureKey, value: !this.features[featureKey] }))
    },
  },

  computed: {
    features(): PlusFeatures {
      return this.state.features
    },
    needsMigration(): boolean {
      return !this.state.isMigrationComplete && this.state.files && this.state.files.length > 0
    },
  },
})
</script>
