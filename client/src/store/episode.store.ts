import { get } from 'lodash'
import { handleActions } from 'redux-actions'
import { createAction } from 'redux-actions'
import Timestamp from '@lib/timestamp'
import * as lifecycle from './lifecycle.store'

export const INIT = 'podlove/publisher/episode/INIT'
export const UPDATE = 'podlove/publisher/episode/UPDATE'
export const SET = 'podlove/publisher/episode/SET'
export const SET_POSTER = 'podlove/publisher/episode/SET_POSTER'
export const SELECT_POSTER = 'podlove/publisher/episode/SELECT_POSTER'

export type State = {
  id: string | null
  duration: number | null
  number: string | null
  title: string | null
  subtitle: string | null
  summary: string | null
  type: 'full' | 'trailer' | 'bonus' | null
  episode_poster: string | null
  poster: string | null
  mnemonic: string | null
  explicit: boolean | null
}

export const initialState: State = {
  id: null,
  duration: null,
  number: null,
  subtitle: null,
  title: null,
  summary: null,
  type: null,
  episode_poster: null,
  poster: null,
  mnemonic: null,
  explicit: null
}

export const update = createAction<{ prop: string; value: string }>(UPDATE)
export const init = createAction<void>(INIT)
export const selectPoster = createAction<void>(SELECT_POSTER)
export const set = createAction<{
  number?: string,
  duration?: string,
  title?: string,
  subtitle?: string,
  summary?: string,
  episode_poster?: string,
  poster?: string,
  mnemonic?: string,
  explicit? : boolean
}>(SET);

export const reducer = handleActions(
  {
    [lifecycle.INIT]: (state: State, action: typeof lifecycle.init): State => ({
      ...state,
      id: get(action, ['payload', 'episode', 'id'], null),
      duration: Timestamp.fromString(get(action, ['payload', 'episode', 'duration'], null)).totalMs,
    }),
    [UPDATE]: (state: State, action: typeof update): State => ({
      ...state,
      [get(action, ['payload', 'prop'])]:  get(action, ['payload', 'value'], null)
    }),
    [SET]: (state: State, action: typeof update): State => ({
      ...state,
      number: get(action, ['payload', 'number']),
      title: get(action, ['payload', 'title_clean']),
      duration: get(action, ['payload', 'duration']),
      subtitle: get(action, ['payload', 'subtitle']),
      summary: get(action, ['payload', 'summary']),
      type: get(action, ['payload', 'type']),
      episode_poster: get(action, ['payload', 'episode_poster']),
      poster: get(action, ['payload', 'poster']),
      mnemonic: get(action, ['payload', 'mnemonic']),
      explicit: get(action, ['payload', 'explicit']),
    }),
  },
  initialState
)

export const selectors = {
  id: (state: State) => state.id,
  duration: (state: State) => state.duration,
  number: (state: State) => state.number,
  title: (state: State) => state.title,
  subtitle: (state: State) => state.subtitle,
  summary: (state: State) => state.summary,
  type: (state: State) => state.type,
  poster: (state: State) => state.episode_poster || state.poster,
  mnemonic: (state: State) => state.mnemonic,
  explicit: (state: State) => state.explicit,
}
