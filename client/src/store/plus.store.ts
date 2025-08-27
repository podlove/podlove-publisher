import { handleActions, createAction } from 'redux-actions'
import * as lifecycle from './lifecycle.store'

export type PlusFeatures = {
  fileStorage: boolean
  feedProxy: boolean
}

export type State = {
  features: PlusFeatures
  token: string
  user: {
    email: string
  } | null
  isLoading: boolean
  isSaving: boolean
}

export const initialState: State = {
  features: {
    fileStorage: false,
    feedProxy: false,
  },
  token: '',
  user: null,
  isLoading: true,
  isSaving: false,
}

export const INIT = 'podlove/publisher/plus/INIT'
export const SET_FEATURE = 'podlove/publisher/plus/SET_FEATURE'
export const GET_TOKEN = 'podlove/publisher/plus/GET_TOKEN'
export const SET_TOKEN = 'podlove/publisher/plus/SET_TOKEN'
export const SET_USER = 'podlove/publisher/plus/SET_USER'
export const SAVE_TOKEN = 'podlove/publisher/plus/SAVE_TOKEN'
export const SET_LOADING = 'podlove/publisher/plus/SET_LOADING'
export const SET_SAVING = 'podlove/publisher/plus/SET_SAVING'

export const init = createAction<void>(INIT)
export const setFeature = createAction<{ feature: string; value: boolean }>(SET_FEATURE)
export const getToken = createAction<void>(GET_TOKEN)
export const setToken = createAction<string>(SET_TOKEN)
export const setUser = createAction<{ email: string } | null>(SET_USER)
export const saveToken = createAction<string>(SAVE_TOKEN)
export const setLoading = createAction<boolean>(SET_LOADING)
export const setSaving = createAction<boolean>(SET_SAVING)

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
    [SET_TOKEN]: (state: State, action: ReturnType<typeof setToken>): State => ({
      ...state,
      token: action.payload,
    }),
    [SET_USER]: (state: State, action: ReturnType<typeof setUser>): State => ({
      ...state,
      user: action.payload,
    }),
    [SET_LOADING]: (state: State, action: ReturnType<typeof setLoading>): State => ({
      ...state,
      isLoading: action.payload,
    }),
    [SET_SAVING]: (state: State, action: ReturnType<typeof setSaving>): State => ({
      ...state,
      isSaving: action.payload,
    }),
  },
  initialState
)

export const selectors = {
  features: (state: State) => state.features,
  token: (state: State) => state.token,
  user: (state: State) => state.user,
  isLoading: (state: State) => state.isLoading,
  isSaving: (state: State) => state.isSaving,
}
