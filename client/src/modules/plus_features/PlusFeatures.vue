<template>
  <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
    <div class="mb-6">
      <h2 class="mb-2 text-xl font-medium text-gray-700">{{ __('Manage Features', 'podlove-podcasting-plugin-for-wordpress') }}</h2>
      <p class="text-sm text-gray-600">
        {{ __('Enable or disable PLUS features. Changes will take effect immediately.', 'podlove-podcasting-plugin-for-wordpress') }}
      </p>
    </div>

    <div class="space-y-3">
      <Feature
        :title="__('Podcast File Hosting', 'podlove-podcasting-plugin-for-wordpress')"
        :modelValue="features.fileStorage"
        @update:modelValue="handleFeatureToggle('fileStorage')"
      >
        <template #settings-action v-if="features.fileStorage">
          <button
            @click="toggleMigrationTool"
            class="p-1 text-gray-400 hover:text-gray-600 transition-colors flex items-center gap-1"
            :title="__('Show Migration Tool', 'podlove-podcasting-plugin-for-wordpress')"
          >
            <Cog6ToothIcon class="size-5" /> <span>{{ __('Show Migration Tool', 'podlove-podcasting-plugin-for-wordpress') }}</span>
          </button>
        </template>

        <p class="text-sm text-gray-600 mb-2">
          {{ __('Keep your podcast files in fast, reliable cloud hosting built for podcast delivery. As your show grows, you can avoid the storage and performance limits of serving files directly from WordPress.', 'podlove-podcasting-plugin-for-wordpress') }}
        </p>

        <p class="text-sm text-gray-600 mb-2">
          {{ __('Enable Podcast File Hosting here to automatically upload your media files and make them available from Publisher PLUS.', 'podlove-podcasting-plugin-for-wordpress') }}
        </p>

        <p class="text-sm text-gray-600">
          {{ __('You can disable it again at any time. Your files will then be served from the WordPress or FTP storage location configured in the plugin.', 'podlove-podcasting-plugin-for-wordpress') }}
        </p>

        <template #footer v-if="features.fileStorage && (needsMigration || showMigrationTool)">
          <PlusFileMigration />
        </template>
      </Feature>

      <Feature
        :title="__('Reliable Feed Delivery', 'podlove-podcasting-plugin-for-wordpress')"
        :modelValue="features.feedProxy"
        @update:modelValue="handleFeatureToggle('feedProxy')"
      >
        <p class="text-sm text-gray-600">
          {{ __('Keep your podcast feed fast and available even during traffic spikes. When enabled, Publisher PLUS automatically routes feed requests through our optimized delivery infrastructure, and you can turn it off again at any time without losing subscribers.', 'podlove-podcasting-plugin-for-wordpress') }}
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
