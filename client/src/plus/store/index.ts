declare global {
  interface Window {
    __REDUX_DEVTOOLS_EXTENSION_COMPOSE__: Function
  }
}

import { createStore, applyMiddleware, compose, Store } from 'redux'
import createSagaMiddleware from 'redux-saga'

import reducers from './reducers'

import { State as LifecycleState } from '../../store/lifecycle.store'
import { State as RuntimeState } from '../../store/runtime.store'
import { State as PlusFileMigrationState } from '../../store/plusFileMigration.store'
import { State as PlusState } from '../../store/plus.store'

import plusSaga from '../sagas/plus.sagas'
import plusFileMigrationSaga from '../sagas/plusFileMigration.sagas'

export interface State {
  lifecycle: LifecycleState
  runtime: RuntimeState
  plusFileMigration: PlusFileMigrationState
  plus: PlusState
}

const sagas = createSagaMiddleware()

const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose
export const store: Store<State> = createStore(reducers, composeEnhancers(applyMiddleware(sagas)))

sagas.run(plusSaga())
sagas.run(plusFileMigrationSaga())

export { sagas }
