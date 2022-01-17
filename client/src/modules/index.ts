import { defineAsyncComponent } from 'vue'

export default {
  PodloveShowNotes: defineAsyncComponent(() => import('./shownotes')),
  PodloveChapters: defineAsyncComponent(() => import('./chapters')),
  PodloveTranscripts: defineAsyncComponent(() => import('./transcripts')),
}
