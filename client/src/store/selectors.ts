import { createSelector } from 'reselect'
import { State } from './index'
import * as lifecycleStore from './lifecycle.store'
import * as chaptersStore from './chapters.store'
import * as episodeStore from './episode.store'
import * as runtimeStore from './runtime.store'
import * as postStore from './post.store'
import * as transcriptsStore from './transcripts.store'
import * as contributorsStore from './contributors.store'
import * as settingsStore from './settings.store'
import * as podcastStore from './podcast.store'
import * as auphonicStore from './auphonic.store'
import * as mediafilesStore from './mediafiles.store'

const root = {
  lifecycle: (state: State) => state.lifecycle,
  chapters: (state: State) => state.chapters,
  podcast: (state: State) => state.podcast,
  episode: (state: State) => state.episode,
  runtime: (state: State) => state.runtime,
  post: (state: State) => state.post,
  transcripts: (state: State) => state.transcripts,
  contributors: (state: State) => state.contributors,
  auphonic: (state: State) => state.auphonic,
  mediafiles: (state: State) => state.mediafiles,
  settings: (state: State) => state.settings,
}

const lifecycle = {
  bootstrapped: createSelector(root.lifecycle, lifecycleStore.selectors.bootstrapped),
}

const auphonic = {
  token: createSelector(root.auphonic, auphonicStore.selectors.token),
  productionId: createSelector(root.auphonic, auphonicStore.selectors.productionId),
  productions: createSelector(root.auphonic, auphonicStore.selectors.productions),
  production: createSelector(root.auphonic, auphonicStore.selectors.production),
  presets: createSelector(root.auphonic, auphonicStore.selectors.presets),
  preset: createSelector(root.auphonic, auphonicStore.selectors.preset),
  productionPayload: createSelector(root.auphonic, auphonicStore.selectors.productionPayload),
  incomingServices: createSelector(root.auphonic, auphonicStore.selectors.incomingServices),
  outgoingServices: createSelector(root.auphonic, auphonicStore.selectors.outgoingServices),
  serviceFiles: createSelector(root.auphonic, auphonicStore.selectors.serviceFiles),
  tracks: createSelector(root.auphonic, auphonicStore.selectors.tracks),
  fileSelections: createSelector(root.auphonic, auphonicStore.selectors.fileSelections),
  currentFileSelection: createSelector(root.auphonic, auphonicStore.selectors.currentFileSelection),
  isSaving: createSelector(root.auphonic, auphonicStore.selectors.isSaving),
  isInitializing: createSelector(root.auphonic, auphonicStore.selectors.isInitializing),
  publishWhenDone: createSelector(root.auphonic, auphonicStore.selectors.publishWhenDone),
}

const podcast = {
  title: createSelector(root.podcast, podcastStore.selectors.title),
  subtitle: createSelector(root.podcast, podcastStore.selectors.subtitle),
  summary: createSelector(root.podcast, podcastStore.selectors.summary),
  mnemonic: createSelector(root.podcast, podcastStore.selectors.mnemonic),
  itunesType: createSelector(root.podcast, podcastStore.selectors.itunesType),
  author: createSelector(root.podcast, podcastStore.selectors.author),
  poster: createSelector(root.podcast, podcastStore.selectors.poster),
  link: createSelector(root.podcast, podcastStore.selectors.link),
}

const chapters = {
  list: createSelector(root.chapters, chaptersStore.selectors.chapters),
  selected: createSelector(root.chapters, chaptersStore.selectors.selected),
  selectedIndex: createSelector(root.chapters, chaptersStore.selectors.selectedIndex),
}

const episode = {
  id: createSelector(root.episode, episodeStore.selectors.id),
  slug: createSelector(root.episode, episodeStore.selectors.slug),
  duration: createSelector(root.episode, episodeStore.selectors.duration),
  number: createSelector(root.episode, episodeStore.selectors.number),
  title: createSelector(root.episode, episodeStore.selectors.title),
  subtitle: createSelector(root.episode, episodeStore.selectors.subtitle),
  summary: createSelector(root.episode, episodeStore.selectors.summary),
  type: createSelector(root.episode, episodeStore.selectors.type),
  poster: createSelector(root.episode, episodeStore.selectors.poster),
  episodePoster: createSelector(root.episode, episodeStore.selectors.episodePoster),
  mnemonic: createSelector(root.episode, episodeStore.selectors.mnemonic),
  explicit: createSelector(root.episode, episodeStore.selectors.explicit),
  auphonicProductionId: createSelector(root.episode, episodeStore.selectors.auphonicProductionId),
  auphonicWebhookConfig: createSelector(root.episode, episodeStore.selectors.auphonicWebhookConfig),
}

const mediafiles = {
  isInitializing: createSelector(root.mediafiles, mediafilesStore.selectors.isInitializing),
  files: createSelector(root.mediafiles, mediafilesStore.selectors.files),
}

const runtime = {
  baseUrl: createSelector(root.runtime, runtimeStore.selectors.baseUrl),
  nonce: createSelector(root.runtime, runtimeStore.selectors.nonce),
  base: createSelector(root.runtime, runtimeStore.selectors.base),
  auth: createSelector(root.runtime, runtimeStore.selectors.auth),
  bearer: createSelector(root.runtime, runtimeStore.selectors.bearer),
}

const post = {
  id: createSelector(root.post, postStore.selectors.id),
  title: createSelector(root.post, postStore.selectors.title),
}

const transcripts = {
  list: createSelector(root.transcripts, transcriptsStore.selectors.transcripts),
  voices: createSelector(root.transcripts, transcriptsStore.selectors.voices),
}

const contributors = {
  list: createSelector(root.contributors, contributorsStore.selectors.list),
}

const settings = {
  autoGenerateEpisodeTitle: createSelector(
    root.settings,
    settingsStore.selectors.autoGenerateEpisodeTitle
  ),
  blogTitleTemplate: createSelector(root.settings, settingsStore.selectors.blogTitleTemplate),
  episodeNumberPadding: createSelector(root.settings, settingsStore.selectors.episodeNumberPadding),
  imageAsset: createSelector(root.settings, settingsStore.selectors.imageAsset),
  enableEpisodeExplicit: createSelector(
    root.settings,
    settingsStore.selectors.enableEpisodeExplicit
  ),
}

export default {
  lifecycle,
  podcast,
  chapters,
  episode,
  runtime,
  post,
  transcripts,
  contributors,
  settings,
  auphonic,
  mediafiles,
}
