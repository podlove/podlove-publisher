<template>
  <div>
    <div class="rounded-md bg-green-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <ClipboardCheckIcon class="h-5 w-5 text-green-400" aria-hidden="true" />
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-green-800">Done</h3>
          <div class="mt-2 text-sm text-green-700">
            <p>
              <a
                :href="production.status_page"
                target="_blank"
                class="underline inline-flex items-center"
                >View Results <ExternalLinkIcon class="ml-0.5 mr-1 h-4 w-4" aria-hidden="true"
              /></a>
              on the Auphonic status page.
            </p>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-4 overflow-hidden rounded-lg bg-white shadow">
      <div class="p-6">
        <div>
          <h3 class="text-lg font-medium leading-6 text-gray-900">Import Metadata</h3>
          <p class="mt-1 text-sm text-gray-500">
            These values from your Auphonic Production differ from your local values:
          </p>
        </div>

        <div class="mt-6 flow-root">
          <ul role="list" class="-my-5 divide-y divide-gray-200">
            <li v-for="entry in visibleEntries" :key="entry.key" class="py-4">
              <div class="flex items-center space-x-4">
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm text-gray-500">
                    <em>{{ entry.title }}</em> in the Auphonic Production is:
                  </p>
                  <p class="truncate text-sm font-medium text-gray-900">{{ entry.there }}</p>
                </div>
                <div>
                  <button
                    @click.prevent="importMeta(entry.title, entry.there)"
                    class="inline-flex items-center rounded-full border border-gray-300 bg-white px-2.5 py-0.5 text-sm font-medium leading-5 text-gray-700 shadow-sm hover:bg-gray-50"
                    aria-label="import from Auphonic"
                  >
                    Import<span class="hidden sm:inline">&nbsp;from Auphonic</span>
                  </button>
                </div>
              </div>
            </li>
          </ul>
        </div>
        <div class="mt-6">
          <button
            @click.prevent="importAllMeta"
            class="flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
          >
            Import all from Auphonic
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { injectStore, mapState } from 'redux-vuex'
import { selectors } from '@store'
import { Production } from '@store/auphonic.store'
import { update as updateEpisode } from '@store/episode.store'

import { ClipboardCheckIcon, ExternalLinkIcon } from '@heroicons/vue/outline'

export default defineComponent({
  components: {
    ClipboardCheckIcon,
    ExternalLinkIcon,
  },

  setup() {
    return {
      state: mapState({
        production: selectors.auphonic.production,
        title: selectors.episode.title,
        subtitle: selectors.episode.subtitle,
        summary: selectors.episode.summary,
        duration: selectors.episode.duration,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    importMeta(key, value) {
      // FIXME: finish implementation once episode saga supports it
      const simple = ['title', 'subtitle', 'summary']
      const other = ['image']
      const todo = ['slug', 'duration', 'tags', 'license', 'license url']

      if (simple.includes(key)) {
        this.dispatch(updateEpisode({ prop: key, value: value }))
      } else if (key == 'image') {
        this.dispatch(updateEpisode({ prop: 'episode_poster', value: value }))
      } else {
        console.debug('todo', key)
      }
    },
    importAllMeta() {
      this.visibleEntries.forEach((entry) => {
        this.importMeta(entry.title, entry.there)
      })
    },
  },

  computed: {
    production(): Production {
      return this.state.production || {}
    },
    entries() {
      const production = this.state.production
      const state = this.state

      // TODO: chapters
      return [
        { key: 1, title: 'title', here: state.title, there: production.metadata.title },
        { key: 2, title: 'subtitle', here: state.subtitle, there: production.metadata.subtitle },
        { key: 3, title: 'summary', here: state.summary, there: production.metadata.summary },
        { key: 4, title: 'tags', here: 'todo', there: production.metadata.tags.join(' , ') },
        { key: 5, title: 'license', here: 'todo', there: production.metadata.license },
        { key: 6, title: 'license url', here: 'todo', there: production.metadata.license_url },
        { key: 7, title: 'image', here: 'todo', there: production.image },
        { key: 8, title: 'duration', here: state.duration, there: production.length_timestring },
        { key: 9, title: 'slug', here: 'todo', there: production.output_basename },
      ]
    },
    visibleEntries() {
      return this.entries.filter((e) => e.there && e.there != e.here)
    },
  },
})
</script>
