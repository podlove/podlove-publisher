import { createAction, handleActions } from 'redux-actions'

export type MediaFile = {
  asset_id: number
  asset: string
  url: string
  size: number
  enable: boolean
  is_verifying: boolean
}

export type State = {
  is_initializing: boolean
  slug_autogeneration_enabled: boolean
  files: MediaFile[]
}

export const initialState: State = {
  is_initializing: true,
  slug_autogeneration_enabled: false,
  files: [],
}

export const INIT = 'podlove/publisher/mediafiles/INIT'
export const INIT_DONE = 'podlove/publisher/mediafiles/INIT_DONE'
export const SET = 'podlove/publisher/mediafiles/SET'
export const UPDATE = 'podlove/publisher/mediafiles/UPDATE'
export const ENABLE = 'podlove/publisher/mediafiles/ENABLE'
export const DISABLE = 'podlove/publisher/mediafiles/DISABLE'
export const VERIFY = 'podlove/publisher/mediafiles/VERIFY'
export const UPLOAD_INTENT = 'podlove/publisher/mediafiles/UPLOAD_INTENT'
export const SET_UPLOAD_URL = 'podlove/publisher/mediafiles/SET_UPLOAD_URL'
export const ENABLE_SLUG_AUTOGEN = 'podlove/publisher/mediafiles/ENABLE_SLUG_AUTOGEN'
export const DISABLE_SLUG_AUTOGEN = 'podlove/publisher/mediafiles/DISABLE_SLUG_AUTOGEN'

export const init = createAction<void>(INIT)
export const initDone = createAction<void>(INIT_DONE)
export const set = createAction<MediaFile[]>(SET)
export const update = createAction<Partial<MediaFile>>(UPDATE)
export const enable = createAction<number>(ENABLE)
export const disable = createAction<number>(DISABLE)
export const verify = createAction<number>(VERIFY)
export const uploadIntent = createAction<void>(UPLOAD_INTENT)
export const setUploadUrl = createAction<string>(SET_UPLOAD_URL)
export const enableSlugAutogen = createAction<void>(ENABLE_SLUG_AUTOGEN)
export const disableSlugAutogen = createAction<void>(DISABLE_SLUG_AUTOGEN)

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
    [UPDATE]: (state: State, action: { type: string; payload: Partial<MediaFile> }): State => ({
      ...state,
      files: state.files.reduce(
        (result: MediaFile[], file) => [
          ...result,
          file.asset_id == action.payload.asset_id ? { ...file, ...action.payload } : file,
        ],
        []
      ),
    }),
    [ENABLE]: (state: State, action: { type: string; payload: number }): State => ({
      ...state,
      files: state.files.reduce(
        (result: MediaFile[], file) => [
          ...result,
          file.asset_id == action.payload ? { ...file, enable: true } : file,
        ],
        []
      ),
    }),
    [DISABLE]: (state: State, action: { type: string; payload: number }): State => ({
      ...state,
      files: state.files.reduce(
        (result: MediaFile[], file) => [
          ...result,
          file.asset_id == action.payload ? { ...file, enable: false } : file,
        ],
        []
      ),
    }),
    [ENABLE_SLUG_AUTOGEN]: (state: State): State => ({
      ...state,
      slug_autogeneration_enabled: true,
    }),
    [DISABLE_SLUG_AUTOGEN]: (state: State): State => ({
      ...state,
      slug_autogeneration_enabled: false,
    }),
  },
  initialState
)

export const selectors = {
  isInitializing: (state: State) => state.is_initializing,
  slugAutogenerationEnabled: (state: State) => state.slug_autogeneration_enabled,
  files: (state: State) => state.files,
}
