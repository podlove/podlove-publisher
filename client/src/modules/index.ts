import { defineAsyncComponent } from 'vue'

export default {
  PodloveShowNotes: defineAsyncComponent(() => import('./shownotes')),
  PodloveChapters: defineAsyncComponent(() => import('./chapters')),
  PodloveContributors: defineAsyncComponent(() => import('./contributors')),
  PodloveTranscripts: defineAsyncComponent(() => import('./transcripts')),
}
