import { defineAsyncComponent } from 'vue'

export default {
  PodloveDescription: defineAsyncComponent(() => import('./description')),
  PodloveChapters: defineAsyncComponent(() => import('./chapters')),
  PodloveTranscripts: defineAsyncComponent(() => import('./transcripts'))
}
