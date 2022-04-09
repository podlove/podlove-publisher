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

export type Preset = Production & {
  preset_name: string
}

export type AudioTrack = {
  identifier: string
  fileSelection: any
  filtering: boolean
  noise_and_hum_reduction: boolean
  fore_background: string
  track_gain: string
}

export type State = {
  token: string | null
  production: Production | null
  productions: Production[] | null
  presets: Preset[] | null
  services: Service[]
  service_files: object
  tracks: AudioTrack[]
}

export const initialState: State = {
  token: null,
  production: null,
  productions: [],
  presets: [],
  services: [],
  service_files: {},
  tracks: [],
}

export const INIT = 'podlove/publisher/auphonic/INIT'
export const SET_TOKEN = 'podlove/publisher/auphonic/SET_TOKEN'
export const SET_PRODUCTION = 'podlove/publisher/auphonic/SET_PRODUCTION'
export const SET_PRODUCTIONS = 'podlove/publisher/auphonic/SET_PRODUCTIONS'
export const SET_SERVICES = 'podlove/publisher/auphonic/SET_SERVICES'
export const CREATE_PRODUCTION = 'podlove/publisher/auphonic/CREATE_PRODUCTION'
export const CREATE_MULTITRACK_PRODUCTION =
  'podlove/publisher/auphonic/CREATE_MULTITRACK_PRODUCTION'
export const SELECT_SERVICE = 'podlove/publisher/auphonic/SELECT_SERVICE'
export const SET_SERVICE_FILES = 'podlove/publisher/auphonic/SET_SERVICE_FILES'
export const UPLOAD_FILE = 'podlove/publisher/auphonic/UPLOAD_FILE'
export const SELECT_TRACKS = 'podlove/publisher/auphonic/SELECT_TRACKS'
export const ADD_TRACK = 'podlove/publisher/auphonic/ADD_TRACK'
export const UPDATE_TRACK = 'podlove/publisher/auphonic/UPDATE_TRACK'
export const SET_PRESETS = 'podlove/publisher/auphonic/SET_PRESETS'

export const init = createAction<void>(INIT)
export const setToken = createAction<string>(SET_TOKEN)

// Productions
export const setProduction = createAction<Production>(SET_PRODUCTION)
export const setProductions = createAction<Production[]>(SET_PRODUCTIONS)
export const createProduction = createAction<string>(CREATE_PRODUCTION)
export const createMultitrackProduction = createAction<string>(CREATE_MULTITRACK_PRODUCTION)

// Presets
export const setPresets = createAction<Preset[]>(SET_PRESETS)

// Files & File Services
export const setServices = createAction<Service[]>(SET_SERVICES)
export const setServiceFiles =
  createAction<{ uuid: string; files: string[] | null }>(SET_SERVICE_FILES)
export const selectService = createAction<string>(SELECT_SERVICE)
export const uploadFile = createAction<File>(UPLOAD_FILE)

// Tracks
export const selectTracks = createAction<string>(SELECT_TRACKS)
export const addTrack = createAction<void>(ADD_TRACK)
export const updateTrack = createAction<{ track: AudioTrack; index: number }>(UPDATE_TRACK)

export const reducer = handleActions(
  {
    [ADD_TRACK]: (state: State, action): State => {
      return {
        ...state,
        tracks: [
          ...state.tracks,
          {
            identifier: `Track ${state.tracks.length + 1}`,
            fileSelection: null,
            filtering: true,
            noise_and_hum_reduction: false,
            fore_background: '0',
            track_gain: '0',
          },
        ],
      }
    },
    [UPDATE_TRACK]: (
      state: State,
      action: { type: string; payload: { track: Partial<AudioTrack>; index: number } }
    ): State => {
      const tracks = state.tracks.reduce(
        (result: AudioTrack[], track, trackIndex) => [
          ...result,
          trackIndex === action.payload.index ? { ...track, ...action.payload.track } : track,
        ],
        []
      )

      return { ...state, tracks }
    },
    [SET_SERVICE_FILES]: (
      state: State,
      action: { payload: { uuid: string; files: string[] | null } }
    ): State => {
      const { uuid, files } = action.payload

      return {
        ...state,
        service_files: { ...state.service_files, [uuid]: files },
      }
    },
    [SET_SERVICES]: (state: State, action: { payload: Service[] }): State => ({
      ...state,
      services: action.payload,
    }),
    [SET_PRESETS]: (state: State, action: { payload: Preset[] | null }): State => ({
      ...state,
      presets: action.payload,
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
  serviceFiles: (state: State) => state.service_files,
  tracks: (state: State) => state.tracks,
}
