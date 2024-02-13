<template>
  <label for="filename_slug" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">{{
    __('Filename / Slug')
  }}</label>
  <div class="mt-2 sm:col-span-2 sm:mt-0">
    <div
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
      />
      <span class="flex slect-none items-center text-gray-500 sm:text-sm pr-2">.ext</span>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'

import { selectors } from '@store'
import { update as updateEpisode } from '@store/episode.store'
import { disableSlugAutogen } from '@store/mediafiles.store'

export default defineComponent({
  setup() {
    return {
      state: mapState({
        slug: selectors.episode.slug,
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
  },

  computed: {
    assetPrefix(): string {
      let url = this.state.baseUri?.replace(/https?:\/\//i, '').trim()

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
