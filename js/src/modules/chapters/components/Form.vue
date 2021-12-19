<template>
  <div class="flex">
    <div class="w-full">
      <ol class="mb-3">
        <li
          @click="selectChapter(index)"
          v-for="(chapter, index) in chapters"
          class="flex text-sm p-1 hover:bg-blue-100 cursor-pointer"
          :class="{
            'bg-white': index % 2,
            'bg-blue-100': index === state.selectedIndex,
          }"
          :key="`chapter-${index}`"
        >
          <div class="font-mono mr-2">
            {{ chapter.start }}
          </div>
          <div class="mr-2 w-full">
            {{ chapter.title }}
          </div>
          <div class="font-mono">
            {{ chapter.duration }}
          </div>
        </li>
      </ol>
      <div class="ml-1">
        <button @click="addChapter()" class="rounded border border-blue-600 px-2 py-1 bg-gray-200 font-light text-sm text-blue-600">+ Add Chapter</button>
      </div>
    </div>
    <div v-if="state.selected" class="w-4/12 mx-4 my-2 mt-0">
      <div class="mb-5">
        <label for="chapter-title" class="ml-1 block mb-2"
          ><span class="font-bold">Title</span></label
        >
        <input
          @change="updateChapter('title', $event.target.value)"
          name="chapter-title"
          type="text"
          class="h-8 p-2 rounded border border-gray-200 w-full text-sm"
          :value="state.selected.title"
        />
      </div>
      <div class="mb-5">
        <label for="chapter-url" class="block ml-1 mb-2"
          ><span class="font-bold uppercase mr-1">Url</span
          ><span class="text-xs">(optional)</span></label
        >
        <input
          @change="updateChapter('url', $event.target.value)"
          name="chapter-url"
          type="text"
          class="h-8 p-2 rounded border border-gray-200 w-full text-sm"
          :value="state.selected.url"
        />
      </div>
      <div class="mb-5">
        <label for="chapter-start" class="block ml-1 mb-2"
          ><span class="font-bold mr-1">Start</span
          ></label
        >
        <input
          @change="updateChapter('start', $event.target.value)"
          name="chapter-start"
          type="text"
          class="h-8 p-2 rounded border border-gray-200 w-full text-sm"
          :value="formatTime(state.selected.start)"
        />
      </div>
      <div class="mb-5 ml-1">
        <button @click="removeChapter()" class="rounded border border-blue-600 px-2 py-1 bg-gray-200 font-light text-sm text-blue-600">Delete Chapter</button>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'
import Timestamp from '@lib/timestamp'
import { PodloveChapter } from '@types/chapters.types'
import { get } from 'lodash'
import { selectors } from '@store'
import { select as selectChapter, update as updateChapter, remove as removeChapter, add as addChapter } from '@store/chapters.store'

interface Chapter {
  index: number
  title: string
  duration: string
  start: string
}

export default defineComponent({
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
    episodeDuration(): number {
      return this.state.episodeDuration
        ? Timestamp.fromString(this.state.episodeDuration).totalMs
        : null
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
          let durationMs: number

          if (!next) {
            durationMs = this.episodeDuration ? this.episodeDuration - chapter.start : -1
          } else {
            durationMs = next.start - chapter.start
          }

          let duration: string

          switch (true) {
            case durationMs === -1:
              duration = 'Unknown'
              break
            case durationMs < 0:
              duration = 'Unkown'
              break
            case this.episodeDuration !== null && durationMs > this.episodeDuration:
              duration = 'Unkown'
              break
            default:
              duration = new Timestamp(durationMs).prettyShort
          }

          return [
            ...result,
            {
              index: chapterIndex,
              title: chapter.title,
              start: new Timestamp(chapter.start).pretty,
              duration,
            },
          ]
        },
        []
      )
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
    updateChapter(prop: 'title' | 'url' | 'start', raw: any) {
      if (this.state.selectedIndex === null) {
        return
      }

      let value: any

      switch (prop) {
        case 'start':
          value = Timestamp.fromString(raw).totalMs
        break;
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
    addChapter() {
      this.dispatch(addChapter())
    },

    // Formatters
    formatTime(value: number): string {
      return new Timestamp(value).pretty
    }

  },
})
</script>

<style></style>
