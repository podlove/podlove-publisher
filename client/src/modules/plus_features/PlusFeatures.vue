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
        title="Podcast File Hosting"
        :modelValue="features.fileStorage"
        @update:modelValue="handleFeatureToggle('fileStorage')"
      >
        <template #settings-action v-if="features.fileStorage">
          <button
            @click="toggleMigrationTool"
            class="p-1 text-gray-400 hover:text-gray-600 transition-colors flex items-center gap-1"
            title="Show Migration Tool"
          >
            <Cog6ToothIcon class="size-5" /> <span>Show Migration Tool</span>
          </button>
        </template>

        <p class="text-sm text-gray-600 mb-2">
          Keep your podcast files in fast, reliable cloud hosting built for podcast delivery. As
          your show grows, you can avoid the storage and performance limits of serving files
          directly from WordPress.
        </p>

        <p class="text-sm text-gray-600 mb-2">
          Enable Podcast File Hosting here to automatically upload your media files and make them
          available from Publisher PLUS.
        </p>

        <p class="text-sm text-gray-600">
          You can disable it again at any time. Your files will then be served from the WordPress
          or FTP storage location configured in the plugin.
        </p>

        <template #footer v-if="features.fileStorage && (needsMigration || showMigrationTool)">
          <PlusFileMigration />
        </template>
      </Feature>

      <Feature
        title="Reliable Feed Delivery"
        :modelValue="features.feedProxy"
        @update:modelValue="handleFeatureToggle('feedProxy')"
      >
        <p class="text-sm text-gray-600">
          Keep your podcast feed fast and available even during traffic spikes. When enabled,
          Publisher PLUS automatically routes feed requests through our optimized delivery
          infrastructure, and you can turn it off again at any time without losing subscribers.
        </p>
      </Feature>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { Cog6ToothIcon } from '@heroicons/vue/24/outline'
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
    Cog6ToothIcon,
  },

  setup() {
    return {
      state: mapState({
        features: selectors.plus.features,
        files: selectors.plusFileMigration.episodesWithFiles,
        isMigrationComplete: selectors.plusFileMigration.isMigrationComplete,
        showMigrationToolManually: selectors.plusFileMigration.showMigrationToolManually,
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
    toggleMigrationTool() {
      this.dispatch(plusFileMigration.toggleMigrationToolManually())
    },
  },

  computed: {
    features(): PlusFeatures {
      return this.state.features
    },
    needsMigration(): boolean {
      return !this.state.isMigrationComplete && this.state.files && this.state.files.length > 0
    },
    showMigrationTool(): boolean {
      return this.state.showMigrationToolManually
    },
  },
})
</script>
