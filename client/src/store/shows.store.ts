import { handleActions, createAction } from 'redux-actions'
import * as lifecycle from './lifecycle.store'

export interface EpisodeShow {
  id: number
  title: string
}

export type State = {
  showList: EpisodeShow[]
}

export const initialState: State = {
  showList: [],
}

export const INIT = 'podlove/publisher/shows/INIT'

export const init = createAction<void>(INIT)

export const reducer = handleActions(
  {
    // [lifecycle.INIT]: (state: State, action: typeof lifecycle.init): State => ({
    //   ...state,
    // }),
  },
  initialState
)

export const selectors = {
  showList: (state: State) => state.showList,
}
