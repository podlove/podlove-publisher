import { get, omit } from 'lodash'
import { handleActions } from 'redux-actions'
import Timestamp from '@lib/timestamp'
import * as lifecycle from './lifecycle.store'
import { createAction } from 'redux-actions'

export const INIT = 'podlove/publisher/episode/INIT'
export const UPDATE = 'podlove/publisher/episode/UPDATE'
export const SET = 'podlove/publisher/episode/SET'

export type State = {
  id: string | null
  duration: number | null
  number: string | null
  title: string | null
  subtitle: string | null
  summary: string | null
}

export const initialState: State = {
  id: null,
  duration: null,
  number: null,
  subtitle: null,
  title: null,
  summary: null,
}

export const update = createAction<{ prop: string; value: string }>(UPDATE)
export const init = createAction<void>(INIT)
export const set = createAction<{
  number?: string,
  duration?: string,
  title?: string,
  subtitle?: string,
  summary?: string
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
}
