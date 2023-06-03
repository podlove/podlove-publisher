import { combineReducers } from 'redux'
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

export default combineReducers({
  lifecycle: lifecycleStore.reducer,
  chapters: chaptersStore.reducer,
  episode: episodeStore.reducer,
  runtime: runtimeStore.reducer,
  post: postStore.reducer,
  transcripts: transcriptsStore.reducer,
  contributors: contributorsStore.reducer,
  settings: settingsStore.reducer,
  podcast: podcastStore.reducer,
  auphonic: auphonicStore.reducer,
  mediafiles: mediafilesStore.reducer,
})
