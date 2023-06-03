declare global {
  interface Window {
    __REDUX_DEVTOOLS_EXTENSION_COMPOSE__: Function
  }
}

import { createStore, applyMiddleware, compose, Store } from 'redux'
import createSagaMiddleware from 'redux-saga'

import selectors from './selectors'
import reducers from './reducers'

import { State as LifecycleState } from './lifecycle.store'
import { State as ChaptersState } from './chapters.store'
import { State as episodeState } from './episode.store'
import { State as runtimeState } from './runtime.store'
import { State as postState } from './post.store'
import { State as transcriptsState } from './transcripts.store'
import { State as contributorsState } from './contributors.store'
import { State as settingsState } from './settings.store'
import { State as podcastState } from './podcast.store'
import { State as auphonicState } from './auphonic.store'
import { State as mediafilesState } from './mediafiles.store'

import lifecycleSaga from '../sagas/lifecycle.sagas'
import podcastSaga from '../sagas/podcast.sagas'
import notificationSaga from '../sagas/notification.saga'
import chaptersSaga from '../sagas/chapters.sagas'
import transcriptsSaga from '../sagas/transcripts.sagas'
import contributorsSaga from '../sagas/contributors.sagas'
import wordpressSaga from '../sagas/wordpress.sagas'
import episodeSaga from '../sagas/episode.sagas'
import auphonicSaga from '../sagas/auphonic.sagas'
import mediafilesSaga from '../sagas/mediafiles.sagas'

export interface State {
  lifecycle: LifecycleState
  chapters: ChaptersState
  episode: episodeState
  runtime: runtimeState
  post: postState
  transcripts: transcriptsState
  contributors: contributorsState
  settings: settingsState
  podcast: podcastState
  auphonic: auphonicState
  mediafiles: mediafilesState
}

const sagas = createSagaMiddleware()

const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose
export const store: Store<State> = createStore(reducers, composeEnhancers(applyMiddleware(sagas)))

sagas.run(lifecycleSaga())
sagas.run(notificationSaga())
sagas.run(chaptersSaga())
sagas.run(transcriptsSaga())
sagas.run(contributorsSaga())
sagas.run(wordpressSaga())
sagas.run(episodeSaga())
sagas.run(podcastSaga())
sagas.run(auphonicSaga())
sagas.run(mediafilesSaga())

export { selectors, sagas }
