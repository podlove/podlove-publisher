<template>
  <label for="filename_slug" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">{{
    __('Filename / Slug', 'podlove-podcasting-plugin-for-wordpress')
  }}</label>
  <div class="mt-2 sm:col-span-2 sm:mt-0">
    <!-- Frozen State: Show as read-only text with edit button -->
    <div
      v-if="state.slugFrozen"
      class="flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 bg-gray-50"
    >
      <span class="flex select-none items-center pl-3 text-gray-500 sm:text-sm">{{
        assetPrefix
      }}</span>
      <span
        class="block flex-1 py-1.5 pl-1 text-gray-900 sm:text-sm sm:leading-6"
      >{{ state.slug }}</span>
      <span class="flex select-none items-center text-gray-500 sm:text-sm pr-2">.ext</span>
      <button
        @click="unfreezeSlug"
        type="button"
        class="ml-2 px-3 py-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 border-l border-gray-300"
        :title="__('Edit slug', 'podlove-podcasting-plugin-for-wordpress')"
        :aria-label="__('Edit filename slug', 'podlove-podcasting-plugin-for-wordpress')"
        aria-describedby="filename_slug_help"
      >
        {{ __('Edit', 'podlove-podcasting-plugin-for-wordpress') }}
      </button>
    </div>

    <!-- Unfrozen State: Show as editable input -->
    <div
      v-else
      class="flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-600"
    >
      <span class="flex select-none items-center pl-3 text-gray-500 sm:text-sm">{{
        assetPrefix
      }}</span>
      <input
        type="text"
        name="filename_slug"
        id="filename_slug"
        autocomplete="filename_slug"
        class="block flex-1 border-0 bg-transparent py-1.5 pl-1 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6"
        placeholder=""
        :value="state.slug"
        @input="updateSlug($event)"
        :aria-label="__('Filename slug', 'podlove-podcasting-plugin-for-wordpress')"
        aria-describedby="filename_slug_help"
      />
      <span class="flex slect-none items-center text-gray-500 sm:text-sm pr-2">.ext</span>
    </div>

    <!-- Screen reader help text -->
    <div id="filename_slug_help" class="sr-only">
      {{ __('Click Edit to modify the filename slug. The slug determines the final filename of your media files.', 'podlove-podcasting-plugin-for-wordpress') }}
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'

import { selectors } from '@store'
import { update as updateEpisode } from '@store/episode.store'
import { disableSlugAutogen, unfreezeSlug } from '@store/mediafiles.store'

export default defineComponent({
  setup() {
    return {
      state: mapState({
        slug: selectors.episode.slug,
        slugFrozen: selectors.episode.slugFrozen,
        id: selectors.episode.id,
        baseUri: selectors.settings.mediaFileBaseUri,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    updateSlug(event: Event) {
      this.dispatch(
        updateEpisode({ prop: 'slug', value: (event.target as HTMLInputElement).value })
      )
      // disable slug generation on any manual input
      this.dispatch(disableSlugAutogen())
    },

    unfreezeSlug() {
      this.dispatch(unfreezeSlug())
    },
  },

  computed: {
    assetPrefix(): string {
      let url = this.state.baseUri?.replace(/https?:\/\//i, '').trim()

      if (!url) {
        return ''
      }

      const lastSlashPos = url.trim().replace(/\/+$/g, '').lastIndexOf('/')

      if (url.length > 30 && lastSlashPos > -1) {
        // only take last subdirectory
        // very.ultra.longdomain.tld/podcast/ => /podcast/
        url = url.slice(lastSlashPos)
      }

      return url
    },
  },
})
</script>
