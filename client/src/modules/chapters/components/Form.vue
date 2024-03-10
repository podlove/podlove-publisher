<template>
  <div v-if="chapters.length > 0">
    <div class="md:flex p-2 sm:block">
      <div class="w-full">
        <div class="h-96 overflow-x-auto" ref="chaptersContainer">
          <table class="min-w-full divide-y divide-gray-200 mb-2">
            <tbody ref="chapters">
              <tr
                @click="selectChapter(index)"
                v-for="(chapter, index) in chapters"
                :key="`chapter-${index}`"
                class="cursor-pointer"
                :class="{
                  'bg-indigo-100': selectedIndex === index,
                  active: selectedIndex === index,
                  'bg-white': index % 2 === 0 && selectedIndex !== index,
                  'bg-gray-50': index % 2 !== 0 && selectedIndex !== index,
                }"
              >
                <td class="px-2 py-2 w-16" v-if="hasChapterImages">
                  <img class="w-12 h-12 rounded" v-if="chapter?.image" :src="chapter?.image" />
                </td>
                <td
                  class="px-3 py-2 w-32 whitespace-nowrap text-sm font-medium text-gray-900 tabular-nums"
                >
                  {{ chapter.start }}
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-600">
                  {{ chapter.title }}
                </td>
                <td
                  class="px-3 py-2 whitespace-nowrap text-sm text-gray-600 text-right tabular-nums"
                >
                  {{ chapter.duration }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-if="state.selected" class="md:w-4/12 sm:w-full md:mx-4 md:my-2 mt-0">
        <div class="mb-5 mt-2">
          <label for="chapter-title" class="block text-sm font-medium text-gray-700">{{
            __('Title')
          }}</label>
          <div class="mt-1">
            <input
              name="chapter-title"
              type="text"
              class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
              @change="updateChapter('title', $event)"
              :value="state.selected.title"
            />
          </div>
        </div>
        <div class="mb-5">
          <label for="chapter-href" class="block text-sm font-medium text-gray-700"
            >Url <span class="text-xs">{{ __('(optional)') }}</span></label
          >
          <div class="mt-1">
            <input
              name="chapter-href"
              type="text"
              class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
              @change="updateChapter('href', $event)"
              :value="state.selected.href"
            />
          </div>
        </div>
        <div class="mb-5">
          <label for="chapter-start" class="block text-sm font-medium text-gray-700">{{
            __('Start')
          }}</label>
          <div class="mt-1">
            <input
              name="chapter-title"
              type="text"
              class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
              @change="updateChapter('start', $event)"
              :value="formatTime(state.selected.start)"
            />
          </div>
        </div>
        <div class="mb-5">
          <label for="chapter-image" class="block text-sm font-medium text-gray-700"
            >{{ __('Image') }} <span class="text-xs">{{ __('(optional)') }}</span></label
          >
          <div class="mt-1 relative">
            <input
              name="chapter-image"
              type="text"
              class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
              @change="updateChapter('image', $event)"
              :value="state.selected.image"
            />
            <button
              @click.prevent="selectImage()"
              :title="__('Select Chapter Image')"
              class="absolute right-2 top-1/2 -mt-3 text-gray-400 hover:text-gray-700"
            >
              <upload-icon class="w-6 h-6" />
            </button>
          </div>
        </div>
        <div class="mb-5 ml-1">
          <podlove-button variant="danger" @click="removeChapter()">{{
            __('Delete Chapter')
          }}</podlove-button>
        </div>
      </div>
    </div>
    <div class="mt-5 ml-5 pb-5">
      <podlove-button variant="primary" @click="addChapter()">
        <plus-sm-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> {{ __('Add Chapter') }}
      </podlove-button>
    </div>
  </div>
  <div v-else class="text-center h-96 flex items-center justify-center flex-col">
    <bookmark-alt-icon class="mx-auto h-12 w-12 text-gray-400" />

    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No chapters') }}</h3>
    <p class="mt-1 text-sm text-gray-500">{{ __('Get started by creating a new chapter.') }}</p>
    <div class="mt-6">
      <podlove-button variant="primary" @click="addChapter()" class="ml-1">
        <plus-sm-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> {{ __('Add Chapter') }}
      </podlove-button>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, nextTick } from 'vue'
import { mapState, injectStore } from 'redux-vuex'
import Timestamp from '@lib/timestamp'
import { get } from 'lodash'
import { selectors } from '@store'
import {
  select as selectChapter,
  update as updateChapter,
  remove as removeChapter,
  add as addChapter,
  selectImage,
} from '@store/chapters.store'
import {
  PlusIcon as PlusSmIcon,
  BookmarkIcon as BookmarkAltIcon,
  ArrowUpTrayIcon as UploadIcon,
} from '@heroicons/vue/24/outline'

import PodloveButton from '@components/button/Button.vue'
import { PodloveChapter } from '../../../types/chapters.types'

interface Chapter {
  index: number
  title: string
  duration: string
  start: string
  image: string
}

export default defineComponent({
  components: { PodloveButton, PlusSmIcon, BookmarkAltIcon, UploadIcon },

  setup() {
    return {
      state: mapState({
        chapters: selectors.chapters.list,
        selected: selectors.chapters.selected,
        selectedIndex: selectors.chapters.selectedIndex,
        episodeDuration: selectors.episode.duration,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  computed: {
    selectedIndex(): number {
      return this.state.selectedIndex
    },
    episodeDuration(): number {
      return this.state.episodeDuration
        ? Timestamp.fromString(this.state.episodeDuration).totalMs
        : 0
    },
    chapters(): Chapter[] {
      return this.state.chapters.reduce(
        (
          result: Chapter[],
          chapter: PodloveChapter,
          chapterIndex: number,
          chapters: PodloveChapter[]
        ) => {
          const next = get(chapters, chapterIndex + 1)
          const isLastChapter: boolean = next === undefined
          let durationMs: number

          if (isLastChapter) {
            durationMs = this.episodeDuration ? this.episodeDuration - (chapter.start || 0) : -1
          } else {
            durationMs = (next.start || 0) - (chapter.start || 0)
          }

          const duration: string =
            durationMs <= 0 ? 'Unknown' : new Timestamp(durationMs).prettyShort

          return [
            ...result,
            {
              index: chapterIndex,
              title: chapter.title,
              start: chapter.start ? new Timestamp(chapter.start).pretty : new Timestamp(0).pretty,
              image: chapter.image,
              duration,
            },
          ]
        },
        []
      )
    },
    hasChapterImages(): boolean {
      return this.chapters.reduce((agg: boolean, chapter: Chapter) => agg || !!chapter.image, false)
    },
  },

  methods: {
    // Store Actions
    selectChapter(index: number) {
      if (index === this.state.selectedIndex) {
        this.dispatch(selectChapter(null))
      } else {
        this.dispatch(selectChapter(index))
      }
    },
    updateChapter(prop: 'title' | 'href' | 'start' | 'image', event: Event) {
      const raw = (event.target as HTMLInputElement).value

      if (this.state.selectedIndex === null) {
        return
      }

      let value: any

      switch (prop) {
        case 'start':
          value = Timestamp.fromString(raw).totalMs
          break
        default:
          value = raw
      }

      this.dispatch(
        updateChapter({
          chapter: {
            [prop]: value,
          },
          index: this.state.selectedIndex,
        })
      )
    },
    removeChapter() {
      this.dispatch(removeChapter(this.state.selectedIndex))
    },
    async addChapter() {
      this.dispatch(addChapter())
      this.dispatch(selectChapter(this.state.chapters.length - 1))

      const container: HTMLElement = this.$refs.chaptersContainer as HTMLElement

      await nextTick()

      container.scrollTo({
        top: container.scrollHeight - container.clientHeight,
        behavior: 'smooth',
      })
    },

    // Formatters
    formatTime(value: number): string {
      return new Timestamp(value).pretty
    },

    selectImage() {
      this.dispatch(selectImage())
    },
  },
})
</script>

<style></style>
