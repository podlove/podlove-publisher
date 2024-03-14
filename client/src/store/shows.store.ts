import { PodloveShow } from '../types/shows.types'
import { get } from 'lodash'
import { handleActions, createAction } from 'redux-actions'

export const INIT = 'podlove/publisher/shows/INIT'
export const SET = 'podlove/publisher/shows/SET'
export const SELECT = 'podlove/publisher/shows/SELECT'

export const init = createAction<void>(INIT)
export const set = createAction<PodloveShow[]>(SET)
export const select = createAction<string>(SELECT)

export type State = {
  shows: PodloveShow[]
}

export const initialState: State = {
  shows: [],
}

export const reducer = handleActions(
  {
    [SET]: (state: State, action: typeof set): State => ({
      ...state,
      shows: get(action, ['payload'], []) as PodloveShow[],
    }),
  },
  initialState
)

export const selectors = {
  shows: (state: State) => state.shows,
}
