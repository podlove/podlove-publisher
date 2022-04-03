import { createAction, handleActions } from 'redux-actions'

export type Service = {
  uuid: string
  display_name: string
  email: string
  incoming: boolean
  outgoing: boolean
  type: string
}

export type Metadata = {
  album: string
  append_chapters: boolean
  artist: string
  genre: string
  license: string
  license_url: string
  publisher: string
  subtitle: string
  summary: string
  tags: string[]
  title: string
  track: string
  url: string
  year: string
}

export type Production = {
  uuid: string
  status: number
  status_string: string
  error_message: string
  error_status: any | null
  warning_message: string
  warning_status: any | null
  edit_page: string
  status_page: string
  waveform_image: string
  image: string | null
  metadata: Metadata
  creation_time: string
  is_multitrack: boolean
}

export type State = {
  token: string | null
  production: Production | null
  productions: Production[] | null
  services: Service[]
}

export const initialState: State = {
  token: null,
  production: null,
  productions: [],
  services: [],
}

export const INIT = 'podlove/publisher/auphonic/INIT'
export const SET_TOKEN = 'podlove/publisher/auphonic/SET_TOKEN'
export const SET_PRODUCTION = 'podlove/publisher/auphonic/SET_PRODUCTION'
export const SET_PRODUCTIONS = 'podlove/publisher/auphonic/SET_PRODUCTIONS'
export const SET_SERVICES = 'podlove/publisher/auphonic/SET_SERVICES'
export const CREATE_PRODUCTION = 'podlove/publisher/auphonic/CREATE_PRODUCTION'
export const CREATE_MULTITRACK_PRODUCTION =
  'podlove/publisher/auphonic/CREATE_MULTITRACK_PRODUCTION'

export const init = createAction<void>(INIT)
export const setToken = createAction<string>(SET_TOKEN)
export const setProduction = createAction<string>(SET_PRODUCTION)
export const setProductions = createAction<string>(SET_PRODUCTIONS)
export const setServices = createAction<string>(SET_SERVICES)
export const createProduction = createAction<string>(CREATE_PRODUCTION)
export const createMultitrackProduction = createAction<string>(CREATE_MULTITRACK_PRODUCTION)

export const reducer = handleActions(
  {
    [SET_SERVICES]: (state: State, action: { payload: Service[] }): State => ({
      ...state,
      services: action.payload,
    }),
    [SET_PRODUCTIONS]: (state: State, action: { payload: Production[] | null }): State => ({
      ...state,
      productions: action.payload,
    }),
    [SET_PRODUCTION]: (state: State, action: { payload: Production | null }): State => ({
      ...state,
      production: action.payload,
    }),
    [SET_TOKEN]: (state: State, action: { payload: string | null }): State => ({
      ...state,
      token: action.payload,
    }),
  },
  initialState
)

export const selectors = {
  token: (state: State) => state.token,
  production: (state: State) => state.production,
  productionId: (state: State) => state.production?.uuid,
  productions: (state: State) => state.productions,
  services: (state: State) => state.services,
  incomingServices: (state: State) => state.services.filter((s: Service) => s.incoming),
  outgoingServices: (state: State) => state.services.filter((s: Service) => s.outgoing),
}
