import { createAction, handleActions } from 'redux-actions'

export type MediaFile = {
  enabled: boolean
  asset_name: string
  url: string
  size: number
}

export type State = {
  is_initializing: boolean
  files: MediaFile[]
}

export const initialState: State = {
  is_initializing: true,
  files: [],
}

export const INIT = 'podlove/publisher/mediafiles/INIT'
export const INIT_DONE = 'podlove/publisher/mediafiles/INIT_DONE'

export const init = createAction<void>(INIT)
export const initDone = createAction<void>(INIT_DONE)

export const reducer = handleActions(
  {
    [INIT_DONE]: (state: State): State => ({
      ...state,
      is_initializing: false,
    }),
  },
  initialState
)

export const selectors = {
  isInitializing: (state: State) => state.is_initializing,
}
