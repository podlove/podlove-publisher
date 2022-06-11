import { defineAsyncComponent } from 'vue'

export default {
  PodloveDescription: defineAsyncComponent(() => import('./description')),
  PodloveShowNotes: defineAsyncComponent(() => import('./shownotes')),
  PodloveChapters: defineAsyncComponent(() => import('./chapters')),
  PodloveTranscripts: defineAsyncComponent(() => import('./transcripts')),
}
