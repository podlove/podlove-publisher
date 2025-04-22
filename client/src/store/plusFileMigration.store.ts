import { handleActions, createAction } from 'redux-actions'
import * as lifecycle from './lifecycle.store'

type UploadState = 'init' | 'ready' | 'in_progress' | 'finished' | 'error'

type UploadFile = {
  name: string
  localUrl: string
  remoteUrl: string
  state: UploadState
}

export type EpisodeWithFiles = {
  episodeName: string
  files: UploadFile[]
}

export type State = {
  totalState: UploadState
  progress: number
  currentEpisodeName: string
  currentFileName: string
  episodesWithFiles: EpisodeWithFiles[]
}

export const initialState: State = {
  totalState: 'init',
  progress: 0,
  currentEpisodeName: '',
  currentFileName: '',
  episodesWithFiles: [],
}

export const INIT = 'podlove/publisher/plusFileMigration/INIT'
export const SET_EPISODES_WITH_FILES = 'podlove/publisher/plusFileMigration/SET_EPISODES_WITH_FILES'
export const SET_TOTAL_STATE = 'podlove/publisher/plusFileMigration/SET_TOTAL_STATE'
export const START_MIGRATION = 'podlove/publisher/plusFileMigration/START_MIGRATION'
export const SET_CURRENT_METADATA = 'podlove/publisher/plusFileMigration/SET_CURRENT_METADATA'
export const SET_FILE_STATE = 'podlove/publisher/plusFileMigration/SET_FILE_STATE'
export const SET_PROGRESS = 'podlove/publisher/plusFileMigration/SET_PROGRESS'

export const init = createAction<void>(INIT)
export const setEpisodesWithFiles =
  createAction<{ episodesWithFiles: EpisodeWithFiles[] }>(SET_EPISODES_WITH_FILES)
export const setTotalState = createAction<{ totalState: UploadState }>(SET_TOTAL_STATE)
export const startMigration = createAction<void>(START_MIGRATION)
export const setCurrentMetadata =
  createAction<{ currentEpisodeName: string; currentFileName: string }>(SET_CURRENT_METADATA)
export const setFileState = createAction<{ filename: string; state: UploadState }>(SET_FILE_STATE)
export const setProgress = createAction<{ progress: number }>(SET_PROGRESS)

export const reducer = handleActions(
  {
    [lifecycle.INIT]: (state: State, action: typeof lifecycle.init): State => ({
      ...state,
    }),
    [SET_EPISODES_WITH_FILES]: (
      state: State,
      action: ReturnType<typeof setEpisodesWithFiles>
    ): State => ({
      ...state,
      episodesWithFiles: action.payload.episodesWithFiles,
    }),
    [SET_TOTAL_STATE]: (state: State, action: ReturnType<typeof setTotalState>): State => ({
      ...state,
      totalState: action.payload.totalState,
    }),
    [SET_CURRENT_METADATA]: (
      state: State,
      action: ReturnType<typeof setCurrentMetadata>
    ): State => ({
      ...state,
      currentEpisodeName: action.payload.currentEpisodeName,
      currentFileName: action.payload.currentFileName,
    }),
    [SET_FILE_STATE]: (state: State, action: ReturnType<typeof setFileState>): State => ({
      ...state,
      episodesWithFiles: state.episodesWithFiles.map((episode) => ({
        ...episode,
        files: episode.files.map((file) => ({
          ...file,
          state: file.name === action.payload.filename ? action.payload.state : file.state,
        })),
      })),
    }),
    [SET_PROGRESS]: (state: State, action: ReturnType<typeof setProgress>): State => ({
      ...state,
      progress: action.payload.progress,
    }),
  },
  initialState
)

export const selectors = {
  totalState: (state: State) => state.totalState,
  progress: (state: State) => state.progress,
  currentEpisodeName: (state: State) => state.currentEpisodeName,
  currentFileName: (state: State) => state.currentFileName,
  episodesWithFiles: (state: State) => state.episodesWithFiles,
}
