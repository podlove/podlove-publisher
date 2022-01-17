<template>
  <div class="flex">
    <div>
      <ol>
        <li v-for="(chapter, index) in chapters" class="flex" :key="`chapter-${index}`">
          <div class="font-mono">
            {{ chapter.start }}
          </div>
          <div>
            {{ chapter.title }}
          </div>
          <div>
            {{ chapter.duration }}
          </div>
        </li>
      </ol>
    </div>
    <div v-if="state.selected"></div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import { mapState } from 'redux-vuex'
import { selectors } from '@store'
import Timestamp from '@lib/timestamp'
import { PodloveChapter } from '@types/chapters.types'
import { get } from "lodash";

interface Chapter {
      index: number;
      title: string;
      duration: string;
      start: string;
}

export default defineComponent({
  setup() {
    return {
      state: mapState({
        chapters: selectors.chapters.list,
        selected: selectors.chapters.selected,
        episodeDuration: selectors.episode.duration
      })
    }
  },

  computed: {
    episodeDuration(): number {
      return this.state.episodeDuration ? Timestamp.fromString(this.state.episodeDuration).totalMs : null
    },
    chapters(): Chapter[] {
    return this.state.chapters.map((chapter: PodloveChapter) => ({ ...chapter, start: Timestamp.fromString(chapter.start) })).reduce((result: Chapter[], chapter: { title: string; start: Timestamp }, chapterIndex: number, chapters: { title: string; start: Timestamp }[]) => {
        const next = get(chapters, chapterIndex + 1)
        let durationMs: number;

        if (!next) {
          durationMs = this.episodeDuration ? this.episodeDuration - chapter.start.totalMs : -1
        } else {
          durationMs = next.start.totalMs - chapter.start.totalMs
        }

        let duration: string;

        switch (true) {
          case durationMs === -1:
            duration = 'Unknown episode length';
            break;
          case this.episodeDuration !== null && durationMs > this.episodeDuration:
            duration = 'Exceeds episode length';
            break;
          default:
            duration = new Timestamp(durationMs).prettyShort

        }

        return [
          ...result,
          {
            index: chapterIndex,
            title: chapter.title,
            start: chapter.start.pretty,
            duration
          }
        ]
      }, [])
    }
  }
})
</script>

<style></style>
