import { createAction, handleActions } from 'redux-actions'
import MediaFiles from 'src/modules/mediafiles'

export type MediaFile = {
  asset_id: number
  asset: string
  url: string
  size: number
  enable: boolean
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
export const SET = 'podlove/publisher/mediafiles/SET'
export const ENABLE = 'podlove/publisher/mediafiles/ENABLE'
export const DISABLE = 'podlove/publisher/mediafiles/DISABLE'

export const init = createAction<void>(INIT)
export const initDone = createAction<void>(INIT_DONE)
export const set = createAction<MediaFile[]>(SET)
export const enable = createAction<number>(ENABLE)
export const disable = createAction<number>(DISABLE)

// TODO: enable revalidates I think?
export const reducer = handleActions(
  {
    [INIT_DONE]: (state: State): State => ({
      ...state,
      is_initializing: false,
    }),
    [SET]: (state: State, action: { type: string; payload: MediaFile[] }): State => ({
      ...state,
      files: action.payload,
    }),
    [ENABLE]: (state: State, action: { type: string; payload: number }): State => ({
      ...state,
      files: state.files.reduce(
        (result: MediaFiles[], file) => [
          ...result,
          file.asset_id == action.payload ? { ...file, enable: true } : file,
        ],
        []
      ),
    }),
    [DISABLE]: (state: State, action: { type: string; payload: number }): State => ({
      ...state,
      files: state.files.reduce(
        (result: MediaFiles[], file) => [
          ...result,
          file.asset_id == action.payload ? { ...file, enable: false } : file,
        ],
        []
      ),
    }),
  },
  initialState
)

export const selectors = {
  isInitializing: (state: State) => state.is_initializing,
  files: (state: State) => state.files,
}
