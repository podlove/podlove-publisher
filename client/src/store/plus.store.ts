import { handleActions, createAction } from 'redux-actions'
import * as lifecycle from './lifecycle.store'

export type State = {
  features: {
    fileStorage: boolean
    feedProxy: boolean
  }
}

export const initialState: State = {
  features: {
    fileStorage: false,
    feedProxy: false,
  },
}

export const INIT = 'podlove/publisher/plus/INIT'
export const SET_FEATURE = 'podlove/publisher/plus/SET_FEATURE'

export const init = createAction<void>(INIT)
export const setFeature = createAction<{ feature: string; value: boolean }>(SET_FEATURE)

export const reducer = handleActions(
  {
    [lifecycle.INIT]: (state: State, action: typeof lifecycle.init): State => ({
      ...state,
    }),
    [SET_FEATURE]: (state: State, action: ReturnType<typeof setFeature>): State => ({
      ...state,
      features: {
        ...state.features,
        [action.payload.feature]: action.payload.value,
      },
    }),
  },
  initialState
)

export const selectors = {
  features: (state: State) => state.features,
}
