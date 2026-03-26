import type { Dispatch, Store, UnknownAction } from 'redux'
import { injectStore, mapState } from 'redux-vuex'

import type { State } from './index'

type AppSelector<T> = (state: State) => T
type AppSelectorMap = Record<string, AppSelector<unknown>>

type AppSelection<T extends AppSelectorMap> = {
  [K in keyof T]: ReturnType<T[K]>
}

export type AppStore = Store<State>
export type AppDispatch = Dispatch<UnknownAction>

export const injectAppStore = (): AppStore => injectStore() as AppStore

export const injectAppDispatch = (): AppDispatch => injectAppStore().dispatch as AppDispatch

export const mapAppState = <T extends AppSelectorMap>(selectors: T): AppSelection<T> =>
  mapState(selectors as Record<string, (state: unknown) => unknown>) as AppSelection<T>
