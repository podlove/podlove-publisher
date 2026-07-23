import { createAction, handleActions } from 'redux-actions'

export type AssemblyAIStatus =
  | 'idle'
  | 'submitting'
  | 'processing'
  | 'importing'
  | 'imported'
  | 'error'

export type State = {
  status: AssemblyAIStatus
  transcript_id: string | null
  error: string | null
  assemblyai_status: string | null
  has_api_key: boolean
}

export const initialState: State = {
  status: 'idle',
  transcript_id: null,
  error: null,
  assemblyai_status: null,
  has_api_key: false,
}

export const INIT = 'podlove/publisher/assemblyai/INIT'
export const SET_STATUS = 'podlove/publisher/assemblyai/SET_STATUS'
export const SET_ERROR = 'podlove/publisher/assemblyai/SET_ERROR'
export const SET_TRANSCRIPT_ID = 'podlove/publisher/assemblyai/SET_TRANSCRIPT_ID'
export const SET_ASSEMBLYAI_STATUS = 'podlove/publisher/assemblyai/SET_ASSEMBLYAI_STATUS'
export const SET_HAS_API_KEY = 'podlove/publisher/assemblyai/SET_HAS_API_KEY'
export const START_TRANSCRIPTION = 'podlove/publisher/assemblyai/START_TRANSCRIPTION'
export const RESET = 'podlove/publisher/assemblyai/RESET'

export const init = createAction<void>(INIT)
export const setStatus = createAction<AssemblyAIStatus>(SET_STATUS)
export const setError = createAction<string | null>(SET_ERROR)
export const setTranscriptId = createAction<string | null>(SET_TRANSCRIPT_ID)
export const setAssemblyAIStatus = createAction<string | null>(SET_ASSEMBLYAI_STATUS)
export const setHasApiKey = createAction<boolean>(SET_HAS_API_KEY)
export const startTranscription = createAction<void>(START_TRANSCRIPTION)
export const reset = createAction<void>(RESET)

export const reducer = handleActions(
  {
    [SET_STATUS]: (state: State, action: { payload: AssemblyAIStatus }): State => ({
      ...state,
      status: action.payload,
    }),
    [SET_ERROR]: (state: State, action: { payload: string | null }): State => ({
      ...state,
      error: action.payload,
    }),
    [SET_TRANSCRIPT_ID]: (state: State, action: { payload: string | null }): State => ({
      ...state,
      transcript_id: action.payload,
    }),
    [SET_ASSEMBLYAI_STATUS]: (state: State, action: { payload: string | null }): State => ({
      ...state,
      assemblyai_status: action.payload,
    }),
    [SET_HAS_API_KEY]: (state: State, action: { payload: boolean }): State => ({
      ...state,
      has_api_key: action.payload,
    }),
    [RESET]: (): State => ({
      ...initialState,
    }),
  },
  initialState
)

export const selectors = {
  status: (state: State) => state.status,
  error: (state: State) => state.error,
  transcript_id: (state: State) => state.transcript_id,
  assemblyai_status: (state: State) => state.assemblyai_status,
  has_api_key: (state: State) => state.has_api_key,
}
