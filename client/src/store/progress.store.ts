import { createAction, handleActions } from 'redux-actions'

export type ProgressStatus = 'init' | 'in_progress' | 'finished' | 'error'

export type ProgressItem = {
  progress: number
  status: ProgressStatus
  message?: string
}

export type State = {
  [key: string]: ProgressItem
}

export type SetProgressPayload = {
  key: string
  progress: number
  status?: ProgressStatus
  message?: string
}

export type SetProgressStatusPayload = {
  key: string
  status: ProgressStatus
  message?: string
}

const initialState: State = {}

export const SET_PROGRESS = 'podlove/publisher/progress/SET_PROGRESS'
export const SET_PROGRESS_STATUS = 'podlove/publisher/progress/SET_PROGRESS_STATUS'
export const RESET_PROGRESS = 'podlove/publisher/progress/RESET_PROGRESS'

export const setProgress = createAction<SetProgressPayload>(SET_PROGRESS)
export const setProgressStatus = createAction<SetProgressStatusPayload>(SET_PROGRESS_STATUS)
export const resetProgress = createAction<string>(RESET_PROGRESS)

export const reducer = handleActions(
  {
    [SET_PROGRESS]: (state: State, action: { type: string; payload: SetProgressPayload }) => {
      const { key, progress, status, message } = action.payload
      const currentItem = state[key] || { progress: 0, status: 'init' }

      return {
        ...state,
        [key]: {
          ...currentItem,
          progress,
          ...(status && { status }),
          ...(message !== undefined && { message }),
        },
      }
    },
    [SET_PROGRESS_STATUS]: (
      state: State,
      action: { type: string; payload: SetProgressStatusPayload }
    ) => {
      const { key, status, message } = action.payload
      const currentItem = state[key] || { progress: 0, status: 'init' }

      return {
        ...state,
        [key]: {
          ...currentItem,
          status,
          ...(message !== undefined && { message }),
        },
      }
    },
    [RESET_PROGRESS]: (state: State, action: { type: string; payload: string }) => {
      const newState = { ...state }
      delete newState[action.payload]
      return newState
    },
  },
  initialState
)

const progress = (state: State, key: string): number => state[key]?.progress ?? 0
const status = (state: State, key: string): ProgressStatus => state[key]?.status ?? 'init'
const message = (state: State, key: string): string | undefined => state[key]?.message

export const selectors = {
  progress,
  status,
  message,
}
