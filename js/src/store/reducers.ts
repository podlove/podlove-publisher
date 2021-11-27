import { combineReducers } from 'redux'
import * as lifecycleStore from './lifecycle.store';
import * as chaptersStore from './chapters.store';
import * as episodeStore from './episode.store'
<<<<<<< HEAD
import * as runtimeStore from './runtime.store'
import * as postStore from './post.store'
import * as transcriptsStore from './transcripts.store'
import * as contributorsStore from './contributors.store'
=======
>>>>>>> 6ca060a4744249c97d016dd3c3b420a4285881e3

export default combineReducers({
  lifecycle: lifecycleStore.reducer,
  chapters: chaptersStore.reducer,
<<<<<<< HEAD
  episode: episodeStore.reducer,
  runtime: runtimeStore.reducer,
  post: postStore.reducer,
  transcripts: transcriptsStore.reducer,
  contributors: contributorsStore.reducer
=======
  episode: episodeStore.reducer
>>>>>>> 6ca060a4744249c97d016dd3c3b420a4285881e3
})
