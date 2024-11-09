import { get } from 'lodash'
import { handleActions } from 'redux-actions'
import { INIT, init } from './lifecycle.store'
import * as wordpressStore from './wordpress.store'

export type State = {
  id: string | null
  title: string | null
  featured_media: object | null
}

export const initialState: State = {
  id: null,
  title: null,
  featured_media: null,
}

export const reducer = handleActions(
  {
    [INIT]: (state: State, action: typeof init): State => ({
      ...state,
      id: get(action, ['payload', 'post', 'id'], null),
      title: get(action, ['payload', 'post', 'title'], null),
      featured_media: get(action, ['payload', 'post', 'featured_media'], null),
    }),
    [wordpressStore.UPDATE]: (
      state: State,
      action: { type: string; payload: { prop: string; value: any } }
    ): State => {
      const prop = get(action, ['payload', 'prop'])
      const value = get(action, ['payload', 'value'], null)
      const allowed_props = ['title', 'featured_media']

      if (allowed_props.includes(prop)) {
        return { ...state, [prop]: value }
      } else {
        return { ...state }
      }
    },
  },
  initialState
)

export const selectors = {
  id: (state: State) => state.id,
  title: (state: State) => state.title,
  featured_media: (state: State) => state.featured_media,
}
