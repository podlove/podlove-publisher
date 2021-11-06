import { combineReducers } from 'redux'
import * as lifecycleStore from './lifecycle.store';
import * as chaptersStore from './chapters.store';
import * as episodeStore from './episode.store'
import * as runtimeStore from './runtime.store'
import * as postStore from './post.store'
import * as transcriptsStore from './transcripts.store'

export default combineReducers({
  lifecycle: lifecycleStore.reducer,
  chapters: chaptersStore.reducer,
  episode: episodeStore.reducer,
  runtime: runtimeStore.reducer,
  post: postStore.reducer,
  transcripts: transcriptsStore.reducer
})
