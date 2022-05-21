import { get } from 'lodash'
import { handleActions } from 'redux-actions'
import { INIT, init } from './lifecycle.store'
import * as wordpressStore from './wordpress.store'

export type State = {
  id: string | null
  title: string | null
}

export const initialState: State = {
  id: null,
  title: null,
}

export const reducer = handleActions(
  {
    [INIT]: (state: State, action: typeof init): State => ({
      ...state,
      id: get(action, ['payload', 'post', 'id'], null),
      title: get(action, ['payload', 'post', 'title'], null),
    }),
    [wordpressStore.UPDATE]: (
      state: State,
      action: { type: string; payload: { prop: string; value: any } }
    ): State => {
      if (action.payload.prop !== 'title') {
        return state
      }

      return {
        ...state,
        title: action.payload.value,
      }
    },
  },
  initialState
)

export const selectors = {
  id: (state: State) => state.id,
  title: (state: State) => state.title,
}
