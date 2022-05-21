import { get } from 'lodash'
import { handleActions } from 'redux-actions'
import Timestamp from '@lib/timestamp'
import { init, INIT } from './lifecycle.store'

export type State = {
  id: string | null
  duration: number | null
}

export const initialState: State = {
  id: null,
  duration: null,
}

export const reducer = handleActions(
  {
    [INIT]: (state: State, action: typeof init): State => ({
      ...state,
      id: get(action, ['payload', 'episode', 'id'], null),
      duration: Timestamp.fromString(get(action, ['payload', 'episode', 'duration'], null)).totalMs,
    }),

  },
  initialState
)

export const selectors = {
  id: (state: State) => state.id,
  duration: (state: State) => state.duration,
}
