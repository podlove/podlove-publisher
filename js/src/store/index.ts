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
import chaptersSaga from '../sagas/chapters.sagas'

export interface State {
  lifecycle: LifecycleState,
  chapters: ChaptersState
}

const sagaMiddleware = createSagaMiddleware()

const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose
export const store: Store<State> = createStore(reducers, composeEnhancers(applyMiddleware(sagaMiddleware)))

const episodeForm = document.querySelector('form.metabox-location-normal') as HTMLElement;

sagaMiddleware.run(chaptersSaga(episodeForm));

export { selectors }
