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
  fileInfo: { file: File; originalName: string; newName: string } | null
}

export const initialState: State = {
  is_initializing: true,
  slug_autogeneration_enabled: false,
  files: [],
  fileInfo: null,
}

export const INIT = 'podlove/publisher/mediafiles/INIT'
export const INIT_DONE = 'podlove/publisher/mediafiles/INIT_DONE'
export const SET = 'podlove/publisher/mediafiles/SET'
export const UPDATE = 'podlove/publisher/mediafiles/UPDATE'
export const ENABLE = 'podlove/publisher/mediafiles/ENABLE'
export const DISABLE = 'podlove/publisher/mediafiles/DISABLE'
export const VERIFY = 'podlove/publisher/mediafiles/VERIFY'
export const UPLOAD_INTENT = 'podlove/publisher/mediafiles/UPLOAD_INTENT'
export const PLUS_UPLOAD_INTENT = 'podlove/publisher/mediafiles/PLUS_UPLOAD_INTENT'
export const SET_UPLOAD_URL = 'podlove/publisher/mediafiles/SET_UPLOAD_URL'
export const ENABLE_SLUG_AUTOGEN = 'podlove/publisher/mediafiles/ENABLE_SLUG_AUTOGEN'
export const DISABLE_SLUG_AUTOGEN = 'podlove/publisher/mediafiles/DISABLE_SLUG_AUTOGEN'
export const FILE_SELECTED = 'podlove/publisher/mediafiles/FILE_SELECTED'
export const SET_FILE_INFO = 'podlove/publisher/mediafiles/SET_FILE_INFO'

export const init = createAction<void>(INIT)
export const initDone = createAction<void>(INIT_DONE)
export const set = createAction<MediaFile[]>(SET)
export const update = createAction<Partial<MediaFile>>(UPDATE)
export const enable = createAction<number>(ENABLE)
export const disable = createAction<number>(DISABLE)
export const verify = createAction<number>(VERIFY)
export const uploadIntent = createAction<void>(UPLOAD_INTENT)
export const plusUploadIntent = createAction<File | null>(PLUS_UPLOAD_INTENT)
export const setUploadUrl = createAction<string>(SET_UPLOAD_URL)
export const enableSlugAutogen = createAction<void>(ENABLE_SLUG_AUTOGEN)
export const disableSlugAutogen = createAction<void>(DISABLE_SLUG_AUTOGEN)
export const fileSelected = (file: File, episodeSlug: string | null) => ({
  type: FILE_SELECTED,
  payload: { file, episodeSlug },
})

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
    [SET_FILE_INFO]: (
      state: State,
      action: {
        type: string
        payload: { file: File; originalName: string; newName: string } | null
      }
    ): State => ({
      ...state,
      fileInfo: action.payload,
    }),
  },
  initialState
)

export const selectors = {
  isInitializing: (state: State) => state.is_initializing,
  slugAutogenerationEnabled: (state: State) => state.slug_autogeneration_enabled,
  files: (state: State) => state.files,
  fileInfo: (state: State) => state.fileInfo,
}

export const actions = {
  fileSelected: (file: File, episodeSlug: string | null) => ({
    type: FILE_SELECTED,
    payload: { file, episodeSlug },
  }),
  setFileInfo: (fileInfo: { file: File; originalName: string; newName: string } | null) => ({
    type: SET_FILE_INFO,
    payload: fileInfo,
  }),
}
