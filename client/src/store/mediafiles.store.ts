import { createAction, handleActions } from 'redux-actions'

export type MediaFile = {
  asset_id: number
  asset: string
  url: string
  size: number
  enable: boolean
  is_verifying: boolean
}

export type FileInfo = {
  file: File
  originalName: string
  newName: string
  fileExists?: boolean
}

export type State = {
  is_initializing: boolean
  slug_autogeneration_enabled: boolean
  files: MediaFile[]
  selectedFiles: FileInfo[]
}

export const initialState: State = {
  is_initializing: true,
  slug_autogeneration_enabled: false,
  files: [],
  selectedFiles: [],
}

export const INIT = 'podlove/publisher/mediafiles/INIT'
export const INIT_DONE = 'podlove/publisher/mediafiles/INIT_DONE'
export const SET = 'podlove/publisher/mediafiles/SET'
export const UPDATE = 'podlove/publisher/mediafiles/UPDATE'
export const ENABLE = 'podlove/publisher/mediafiles/ENABLE'
export const DISABLE = 'podlove/publisher/mediafiles/DISABLE'
export const VERIFY = 'podlove/publisher/mediafiles/VERIFY'
export const VERIFY_ALL = 'podlove/publisher/mediafiles/VERIFY_ALL'
export const UPLOAD_INTENT = 'podlove/publisher/mediafiles/UPLOAD_INTENT'
export const PLUS_UPLOAD_INTENT = 'podlove/publisher/mediafiles/PLUS_UPLOAD_INTENT'
export const SET_UPLOAD_URL = 'podlove/publisher/mediafiles/SET_UPLOAD_URL'
export const ENABLE_SLUG_AUTOGEN = 'podlove/publisher/mediafiles/ENABLE_SLUG_AUTOGEN'
export const DISABLE_SLUG_AUTOGEN = 'podlove/publisher/mediafiles/DISABLE_SLUG_AUTOGEN'
export const FILE_SELECTED = 'podlove/publisher/mediafiles/FILE_SELECTED'
export const SET_FILE_INFO = 'podlove/publisher/mediafiles/SET_FILE_INFO'
export const ADD_SELECTED_FILES = 'podlove/publisher/mediafiles/ADD_SELECTED_FILES'
export const REMOVE_SELECTED_FILE = 'podlove/publisher/mediafiles/REMOVE_SELECTED_FILE'
export const UNFREEZE_SLUG = 'podlove/publisher/mediafiles/UNFREEZE_SLUG'

export const init = createAction<void>(INIT)
export const initDone = createAction<void>(INIT_DONE)
export const set = createAction<MediaFile[]>(SET)
export const update = createAction<Partial<MediaFile>>(UPDATE)
export const enable = createAction<number>(ENABLE)
export const disable = createAction<number>(DISABLE)
export const verify = createAction<number>(VERIFY)
export const verifyAll = createAction<void>(VERIFY_ALL)
export const uploadIntent = createAction<void>(UPLOAD_INTENT)
export const plusUploadIntent = createAction<File | null>(PLUS_UPLOAD_INTENT)
export const setUploadUrl = createAction<string>(SET_UPLOAD_URL)
export const enableSlugAutogen = createAction<void>(ENABLE_SLUG_AUTOGEN)
export const disableSlugAutogen = createAction<void>(DISABLE_SLUG_AUTOGEN)
export const fileSelected = (files: File[], episodeSlug: string | null) => ({
  type: FILE_SELECTED,
  payload: { files, episodeSlug },
})

export const addSelectedFiles = createAction<FileInfo[]>(ADD_SELECTED_FILES)
export const removeSelectedFile = createAction<string>(REMOVE_SELECTED_FILE)
export const unfreezeSlug = createAction<void>(UNFREEZE_SLUG)

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
        payload: FileInfo[]
      }
    ): State => ({
      ...state,
      selectedFiles: action.payload,
    }),
    [ADD_SELECTED_FILES]: (
      state: State,
      action: {
        type: string
        payload: FileInfo[]
      }
    ): State => ({
      ...state,
      selectedFiles: [...state.selectedFiles, ...action.payload],
    }),
    [REMOVE_SELECTED_FILE]: (
      state: State,
      action: {
        type: string
        payload: string
      }
    ): State => ({
      ...state,
      selectedFiles: state.selectedFiles.filter(f => f.newName !== action.payload),
    }),
  },
  initialState
)

export const selectors = {
  isInitializing: (state: State) => state.is_initializing,
  slugAutogenerationEnabled: (state: State) => state.slug_autogeneration_enabled,
  files: (state: State) => state.files,
  selectedFiles: (state: State) => state.selectedFiles,
}

export const actions = {
  fileSelected: (files: File[], episodeSlug: string | null) => ({
    type: FILE_SELECTED,
    payload: { files, episodeSlug },
  }),
  setSelectedFiles: (selectedFiles: FileInfo[]) => ({
    type: SET_FILE_INFO,
    payload: selectedFiles,
  }),
  addSelectedFiles: (selectedFiles: FileInfo[]) => ({
    type: ADD_SELECTED_FILES,
    payload: selectedFiles,
  }),
  removeSelectedFile: (fileName: string) => ({
    type: REMOVE_SELECTED_FILE,
    payload: fileName,
  }),
}
