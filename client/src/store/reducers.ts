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
import * as progressStore from './progress.store'
import * as mediafilesStore from './mediafiles.store'
import * as relatedEpisodesStore from './relatedEpisodes.store'
import * as showsStore from './shows.store'
import * as adminStore from './admin.store'
import * as plusFileMigrationStore from './plusFileMigration.store'
import * as plusStore from './plus.store'

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
  progress: progressStore.reducer,
  mediafiles: mediafilesStore.reducer,
  relatedEpisodes: relatedEpisodesStore.reducer,
  shows: showsStore.reducer,
  admin: adminStore.reducer,
  plusFileMigration: plusFileMigrationStore.reducer,
  plus: plusStore.reducer,
})
