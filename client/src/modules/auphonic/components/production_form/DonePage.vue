<template>
  <div>
    <div class="rounded-md bg-green-50 p-4" v-if="production.status == 3">
      <div class="flex">
        <div class="flex-shrink-0">
          <ClipboardCheckIcon class="h-5 w-5 text-green-400" aria-hidden="true" />
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-green-800">{{ __('Done', 'podlove-podcasting-plugin-for-wordpress') }}</h3>
          <div class="mt-2 text-sm text-green-700">
            <p>
              <a
                :href="production.status_page"
                target="_blank"
                class="underline inline-flex items-center"
                >{{ __('View Results', 'podlove-podcasting-plugin-for-wordpress') }}
                <ExternalLinkIcon class="ml-0.5 mr-1 h-4 w-4" aria-hidden="true"
              /></a>
              {{ __('on the Auphonic status page.', 'podlove-podcasting-plugin-for-wordpress') }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="rounded-md bg-yellow-50 p-4 mt-4" v-if="production.warning_message">
      <div class="flex">
        <div class="flex-shrink-0">
          <ExclamationIcon class="h-5 w-5 text-yellow-400" aria-hidden="true" />
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-yellow-800">{{ __('Warning', 'podlove-podcasting-plugin-for-wordpress') }}</h3>
          <div class="mt-2 text-sm text-yellow-700">
            <p>
              {{ production.warning_message }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-lg bg-white shadow" v-if="visibleEntries.length > 0">
      <div class="p-6">
        <div>
          <h3 class="text-lg font-medium leading-6 text-gray-900">Import Metadata</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ __('These values from your Auphonic Production differ from your local values:', 'podlove-podcasting-plugin-for-wordpress') }}
          </p>
        </div>

        <div class="mt-6 flow-root">
          <ul role="list" class="-my-5 divide-y divide-gray-200">
            <li v-for="entry in visibleEntries" :key="entry.key" class="py-4">
              <div class="flex items-center space-x-4">
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm text-gray-500">
                    <!-- TODO: needs better translation support, see https://github.com/podlove/podlove-publisher/issues/1337 -->
                    <em>{{ entry.title }}</em> {{ __('in the Auphonic Production is:', 'podlove-podcasting-plugin-for-wordpress') }}
                  </p>
                  <p class="truncate text-sm font-medium text-gray-900">
                    {{ renderEntryPreview(entry) }}
                  </p>
                </div>
                <div>
                  <button
                    @click.prevent="importMeta(entry.title, entry.there)"
                    class="inline-flex items-center rounded-full border border-gray-300 bg-white px-2.5 py-0.5 text-sm font-medium leading-5 text-gray-700 shadow-sm hover:bg-gray-50"
                    aria-label="Import from Auphonic"
                  >
                    <!-- TODO: needs better translation support, see https://github.com/podlove/podlove-publisher/issues/1337 -->
                    {{ __('Import', 'podlove-podcasting-plugin-for-wordpress')
                    }}<span class="hidden sm:inline">&nbsp;{{ __('from Auphonic', 'podlove-podcasting-plugin-for-wordpress') }}</span>
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
            {{ __('Import all from Auphonic', 'podlove-podcasting-plugin-for-wordpress') }}
          </button>
        </div>
      </div>
    </div>
    <div v-else class="mt-4 overflow-hidden rounded-lg bg-white shadow">
      <div class="p-6">{{ __('Nothing to import', 'podlove-podcasting-plugin-for-wordpress') }}</div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { injectStore, mapState } from 'redux-vuex'
import { selectors } from '@store'
import { AuphonicChapter, Production } from '@store/auphonic.store'
import { update as updateEpisode } from '@store/episode.store'
import { parsed as parsedChapters } from '@store/chapters.store'

import {
  ClipboardDocumentCheckIcon as ClipboardCheckIcon,
  ArrowTopRightOnSquareIcon as ExternalLinkIcon,
  ExclamationTriangleIcon as ExclamationIcon,
} from '@heroicons/vue/24/outline'
import { PodloveChapter } from '../../../../types/chapters.types'

type Entry = {
  key: number
  title: string
  here: any
  there: any
}

export default defineComponent({
  components: {
    ClipboardCheckIcon,
    ExternalLinkIcon,
    ExclamationIcon,
  },

  setup() {
    return {
      state: mapState({
        production: selectors.auphonic.production,
        title: selectors.episode.title,
        subtitle: selectors.episode.subtitle,
        summary: selectors.episode.summary,
        duration: selectors.episode.duration,
        slug: selectors.episode.slug,
        license_name: selectors.episode.license_name,
        license_url: selectors.episode.license_url,
        chapters: selectors.chapters.list,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    isDifferent(entry: Entry) {
      switch (entry.title) {
        case 'chapters':
          const here = entry.here
            .map((chapter: PodloveChapter) => {
              return (
                chapter.start + (chapter.title || '') + (chapter.href || '') + (chapter.image || '')
              )
            })
            .join(';')

          const there = entry.there
            .map((chapter: AuphonicChapter) => {
              return (
                Math.round((chapter.start_sec || 0) * 1000) +
                (chapter.title || '') +
                (chapter.url || '') +
                (chapter.image || '')
              )
            })
            .join(';')

          return here != there
          break

        default:
          return entry.there != entry.here
          break
      }
    },
    renderEntryPreview(entry: Entry) {
      switch (entry.title) {
        case 'chapters':
          const auphonicChapters: AuphonicChapter[] = entry.there
          return auphonicChapters.map((chapter) => chapter.start + ' ' + chapter.title).join(' / ')
          break

        default:
          return entry.there
          break
      }
    },
    importMeta(prop: string, value: any) {
      switch (prop) {
        case 'chapters':
          const auphonicChapters: AuphonicChapter[] = value
          const chapters: PodloveChapter[] = auphonicChapters.map((chapter) => {
            return {
              start: Math.round((chapter.start_sec || 0) * 1000),
              title: chapter.title || '',
              href: chapter.url || '',
              // FIXME: chapter.image is an Auphonic URL which we can't use. We
              // have to download the image and serve from WordPress.
              // image: chapter.image || '',
              image: '',
            }
          })

          this.dispatch(parsedChapters(chapters))
          break

        default:
          this.dispatch(updateEpisode({ prop, value }))
          break
      }
    },
    importAllMeta() {
      this.visibleEntries.forEach((entry: Entry) => {
        this.importMeta(entry.title, entry.there)
      })
    },
  },

  computed: {
    production(): Production {
      return this.state.production || {}
    },
    entries(): any {
      const production = this.state.production
      const state = this.state

      return [
        { key: 1, title: 'title', here: state.title, there: production.metadata.title },
        { key: 2, title: 'subtitle', here: state.subtitle, there: production.metadata.subtitle },
        { key: 3, title: 'summary', here: state.summary, there: production.metadata.summary },
        // { key: 4, title: 'tags', here: 'todo', there: production.metadata.tags.join(' , ') },
        {
          key: 5,
          title: 'license_name',
          here: state.license_name,
          there: production.metadata.license,
        },
        {
          key: 6,
          title: 'license_url',
          here: state.license_url,
          there: production.metadata.license_url,
        },
        // { key: 7, title: 'image', here: 'todo', there: production.image },
        // { key: 8, title: 'duration', here: state.duration, there: production.length_timestring },
        { key: 9, title: 'slug', here: state.slug, there: production.output_basename },
        { key: 10, title: 'chapters', here: state.chapters, there: production.chapters },
      ]
    },
    visibleEntries(): Entry[] {
      return this.entries.filter((e: Entry) => e.there && this.isDifferent(e))
    },
  },
})
</script>
