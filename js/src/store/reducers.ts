import { combineReducers } from 'redux'
import * as lifecycleStore from './lifecycle.store';
import * as chaptersStore from './chapters.store';
import * as episodeStore from './episode.store'

export default combineReducers({
  lifecycle: lifecycleStore.reducer,
  chapters: chaptersStore.reducer,
  episode: episodeStore.reducer
})
