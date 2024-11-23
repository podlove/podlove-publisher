import { createAction, handleActions } from 'redux-actions'

export type State = {
  [key: string]: number
}

export type SetProgressPayload = {
  key: string
  progress: number
}

const initialState: State = {}

export const SET_PROGRESS = 'podlove/publisher/progress/SET_PROGRESS'
export const RESET_PROGRESS = 'podlove/publisher/progress/RESET_PROGRESS'

export const setProgress = createAction<SetProgressPayload>(SET_PROGRESS)
export const resetProgress = createAction<string>(RESET_PROGRESS)

export const reducer = handleActions(
  {
    [SET_PROGRESS]: (state: State, action: { type: string; payload: SetProgressPayload }) => ({
      ...state,
      [action.payload.key]: action.payload.progress,
    }),
    [RESET_PROGRESS]: (state: State, action: { type: string; payload: string }) => {
      const newState = { ...state }
      delete newState[action.payload]
      return newState
    },
  },
  initialState
)

const progress = (state: State, key: string): number => state[key] ?? 0

export const selectors = {
  progress,
}
