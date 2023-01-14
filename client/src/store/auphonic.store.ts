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

export type AuphonicChapter = {
  start: string
  title: string
  image?: string
  url?: string
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
  multi_input_files: AuphonicInputFile[]
  input_file: string
  chapters: AuphonicChapter[]
  output_basename: string
  output_files?: AuphonicOutputFile[]
  outgoing_services: object[]
  algorithms: object
  speech_recognition: object
  service: string | null
}

export type AuphonicInputFile = {
  id: string
  input_file: string
  input_filetype: string
  input_length: number
  service: string | null
  type: 'multitrack' | string
  offset: number
  input_channels: number
  input_bitrate: number
  input_samplerate: number
  algorithms: AuphonicTrackAlgorithms
}

export type AuphonicOutputFile = {
  format: string
  bitrate: string
  suffix: string
  ending: string
  filename: string
  mono_mixdown: boolean
  split_on_chapters: boolean
  outgoing_services: string[]
}

export type AuphonicTrackAlgorithms = {
  backforeground: string
  denoise: boolean
  denoiseamount: number
  hipfilter: boolean
}

export type Preset = Production & {
  preset_name: string
}

export type AudioTrack = {
  identifier: string
  identifier_new: string
  fileSelection: any
  input_file_name: string
  filtering: boolean
  noise_and_hum_reduction: boolean
  fore_background: string
  track_gain: string
  save_state: 'new' | 'unchanged' | 'edited' | 'deleted'
}

export type FileSelection = {
  urlValue: string | null
  fileValue: string | null
  currentServiceSelection: string | null
  fileSelection: string | null
}

export type State = {
  token: string | null
  production: Production | null
  productions: Production[] | null
  presets: Preset[] | null
  preset: Preset | null
  services: Service[]
  service_files: object
  tracks: AudioTrack[]
  file_selections: object
  current_file_selection: string | null
  is_saving: boolean
  is_initializing: boolean
  publish_when_done: boolean
}

export const initialState: State = {
  token: null,
  production: null,
  productions: [],
  presets: [],
  preset: null,
  services: [],
  service_files: {},
  tracks: [],
  file_selections: {},
  current_file_selection: null,
  is_saving: false,
  is_initializing: true,
  publish_when_done: false,
}

export const INIT = 'podlove/publisher/auphonic/INIT'
export const INIT_DONE = 'podlove/publisher/auphonic/INIT_DONE'
export const SET_TOKEN = 'podlove/publisher/auphonic/SET_TOKEN'
export const SET_PRODUCTION = 'podlove/publisher/auphonic/SET_PRODUCTION'
export const SET_PRODUCTIONS = 'podlove/publisher/auphonic/SET_PRODUCTIONS'
export const SET_SERVICES = 'podlove/publisher/auphonic/SET_SERVICES'
export const CREATE_PRODUCTION = 'podlove/publisher/auphonic/CREATE_PRODUCTION'
export const CREATE_MULTITRACK_PRODUCTION =
  'podlove/publisher/auphonic/CREATE_MULTITRACK_PRODUCTION'
export const SAVE_PRODUCTION = 'podlove/publisher/auphonic/SAVE_PRODUCTION'
export const START_PRODUCTION = 'podlove/publisher/auphonic/START_PRODUCTION'
export const DESELECT_PRODUCTION = 'podlove/publisher/auphonic/DESELECT_PRODUCTION'
export const SELECT_SERVICE = 'podlove/publisher/auphonic/SELECT_SERVICE'
export const SET_SERVICE_FILES = 'podlove/publisher/auphonic/SET_SERVICE_FILES'
export const SELECT_TRACKS = 'podlove/publisher/auphonic/SELECT_TRACKS'
export const ADD_TRACK = 'podlove/publisher/auphonic/ADD_TRACK'
export const UPDATE_TRACK = 'podlove/publisher/auphonic/UPDATE_TRACK'
export const SET_PRESETS = 'podlove/publisher/auphonic/SET_PRESETS'
export const SET_PRESET = 'podlove/publisher/auphonic/SET_PRESET'
export const UPDATE_FILE_SELECTION = 'podlove/publisher/auphonic/UPDATE_FILE_SELECTION'
export const START_POLLING = 'podlove/publisher/auphonic/START_POLLING'
export const STOP_POLLING = 'podlove/publisher/auphonic/STOP_POLLING'
export const START_SAVING = 'podlove/publisher/auphonic/START_SAVING'
export const STOP_SAVING = 'podlove/publisher/auphonic/STOP_SAVING'
export const UPDATE_WEBHOOK = 'podlove/publisher/auphonic/UPDATE_WEBHOOK'

export const init = createAction<void>(INIT)
export const initDone = createAction<void>(INIT_DONE)
export const setToken = createAction<string>(SET_TOKEN)

// Productions
export const setProduction = createAction<Production>(SET_PRODUCTION)
export const deselectProduction = createAction<Production>(DESELECT_PRODUCTION)
export const setProductions = createAction<Production[]>(SET_PRODUCTIONS)
export const createProduction = createAction<string>(CREATE_PRODUCTION)
export const createMultitrackProduction = createAction<string>(CREATE_MULTITRACK_PRODUCTION)
export const saveProduction = createAction<Production>(SAVE_PRODUCTION)
export const startProduction = createAction<Production>(START_PRODUCTION)

// Presets
export const setPresets = createAction<Preset[]>(SET_PRESETS)
export const setPreset = createAction<Preset>(SET_PRESET)

// Files & File Services
export const setServices = createAction<Service[]>(SET_SERVICES)
export const setServiceFiles =
  createAction<{ uuid: string; files: string[] | null }>(SET_SERVICE_FILES)
export const selectService = createAction<string>(SELECT_SERVICE)
export const updateFileSelection =
  createAction<{ key: string; prop: string; value: string | null }>(UPDATE_FILE_SELECTION)

// Tracks
export const selectTracks = createAction<string>(SELECT_TRACKS)
export const addTrack = createAction<void>(ADD_TRACK)
export const updateTrack = createAction<{ track: Partial<AudioTrack>; index: number }>(UPDATE_TRACK)

// Polling
export const startPolling = createAction<void>(START_POLLING)
export const stopPolling = createAction<void>(STOP_POLLING)

// Saving State
export const startSaving = createAction<void>(START_SAVING)
export const stopSaving = createAction<void>(STOP_SAVING)

// Webhook
export const updateWebhook = createAction<boolean>(UPDATE_WEBHOOK)

export const reducer = handleActions(
  {
    [INIT_DONE]: (state: State): State => ({
      ...state,
      is_initializing: false,
    }),
    [UPDATE_FILE_SELECTION]: (
      state: State,
      action: { type: string; payload: { key: string; prop: string; value: string | null } }
    ): State => {
      // FIXME: mark track as modified when selection changes
      return {
        ...state,
        current_file_selection: action.payload.key,
        file_selections: {
          ...state.file_selections,
          [action.payload.key]: {
            //@ts-ignore
            ...state.file_selections[action.payload.key],
            [action.payload.prop]: action.payload.value,
          },
        },
      }
    },
    [ADD_TRACK]: (state: State, action: any): State => {
      const id = `Track ${state.tracks.length + 1}`

      return {
        ...state,
        tracks: [
          ...state.tracks,
          {
            identifier: id,
            identifier_new: id,
            fileSelection: null,
            input_file_name: '',
            filtering: true,
            noise_and_hum_reduction: false,
            fore_background: 'auto',
            track_gain: '0',
            save_state: 'new',
          },
        ],
      }
    },
    [UPDATE_TRACK]: (
      state: State,
      action: { type: string; payload: { track: Partial<AudioTrack>; index: number } }
    ): State => {
      // save_state: 'new' | 'unchanged' | 'edited' | 'deleted'
      const track_save_state = (
        track: Partial<AudioTrack>,
        track_payload: Partial<AudioTrack>
      ): Partial<AudioTrack> => {
        const old_state = track.save_state

        if (old_state == 'new') {
          return { save_state: 'new' }
        }

        return { save_state: 'edited' }
      }

      const tracks = state.tracks.reduce(
        (result: AudioTrack[], track, trackIndex) => [
          ...result,
          trackIndex === action.payload.index
            ? {
                ...track,
                ...action.payload.track,
                ...track_save_state(track, action.payload.track),
              }
            : track,
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
    [SET_PRODUCTION]: (state: State, action: { payload: Production | null }): State => {
      const production = action.payload

      const file_selections = () => {
        if (production?.is_multitrack) {
          return (
            production?.multi_input_files?.reduce((acc, file, index) => {
              let service = file.service

              if (!service) {
                if (file.input_file.substring(0, 4) == 'http') {
                  service = 'url'
                } else {
                  service = 'file'
                }
              }

              return {
                ...acc,
                [`${production?.uuid}_t${index}`]: {
                  currentServiceSelection: service,
                  fileSelection: file.service ? file.input_file : null,
                  urlValue: service == 'url' ? file.input_file : null,
                  fileValue: null,
                } as FileSelection,
              }
            }, {}) || {}
          )
        } else {
          // single track

          let service = production?.service
          const input_file = production?.input_file

          if (!service) {
            if (input_file?.substring(0, 4) == 'http') {
              service = 'url'
            } else {
              service = 'file'
            }
          }

          return {
            [`${production?.uuid}`]: {
              currentServiceSelection: service,
              fileSelection: service ? input_file : null,
              urlValue: service == 'url' ? input_file : null,
              fileValue: null,
            } as FileSelection,
          }
        }
      }

      return {
        ...state,
        production: production,
        file_selections: file_selections(),
        tracks:
          action.payload?.multi_input_files?.reduce((acc, file) => {
            return [
              ...acc,
              {
                identifier: file.id,
                identifier_new: file.id,
                filtering: file.algorithms.hipfilter,
                noise_and_hum_reduction: file.algorithms.denoise,
                fore_background: file.algorithms.backforeground,
                input_file_name: file.input_file,
                save_state: 'unchanged',
              } as AudioTrack,
            ]
          }, [] as AudioTrack[]) || [],
      }
    },
    [DESELECT_PRODUCTION]: (state: State): State => ({
      ...state,
      production: null,
      tracks: [],
      file_selections: [],
      current_file_selection: null,
    }),
    [SET_PRESET]: (state: State, action: { payload: Preset | null }): State => ({
      ...state,
      preset: action.payload,
    }),
    [SET_TOKEN]: (state: State, action: { payload: string | null }): State => ({
      ...state,
      token: action.payload,
    }),
    [START_SAVING]: (state: State, action: { payload: null }): State => ({
      ...state,
      is_saving: true,
    }),
    [STOP_SAVING]: (state: State, action: { payload: null }): State => ({
      ...state,
      is_saving: false,
    }),
    [UPDATE_WEBHOOK]: (state: State, action: { payload: boolean }): State => ({
      ...state,
      publish_when_done: action.payload,
    }),
  },
  initialState
)

const chaptersPayload = (chapters: AuphonicChapter[] | undefined) => {
  if (!chapters) {
    return []
  }

  return chapters.map((chapter) => {
    let payload: {
      start: string
      title: string
      image?: string
      url?: string
    } = {
      start: chapter.start,
      title: chapter.title,
    }

    if (chapter.image) {
      payload.image = chapter.image
    }

    if (chapter.url) {
      payload.url = chapter.url
    }

    return payload
  })
}

const outputFilesPayload = (output_files: AuphonicOutputFile[] | undefined) => {
  if (!output_files) {
    return []
  }

  return output_files.map((file) => {
    return {
      format: file.format,
      bitrate: file.bitrate,
      suffix: file.suffix,
      ending: file.ending,
      filename: file.filename,
      mono_mixdown: file.mono_mixdown,
      split_on_chapters: file.split_on_chapters,
      outgoing_services: file.outgoing_services,
    }
  })
}

const productionPayload = (state: State) => {
  const production = state.production

  return {
    uuid: production?.uuid,
    metadata: production?.metadata,
    input_file: production?.input_file,
    chapters: chaptersPayload(production?.chapters),
    output_files: outputFilesPayload(production?.output_files),
    output_basename: production?.output_basename,
    outgoing_services: production?.outgoing_services,
    algorithms: production?.algorithms,
    speech_recognition: production?.speech_recognition,
  }
}

export const selectors = {
  token: (state: State) => state.token,
  production: (state: State) => state.production,
  productionId: (state: State) => state.production?.uuid,
  productions: (state: State) => state.productions,
  presets: (state: State) => state.presets,
  preset: (state: State) => state.preset,
  productionPayload,
  services: (state: State) => state.services,
  incomingServices: (state: State) => state.services.filter((s: Service) => s.incoming),
  outgoingServices: (state: State) => state.services.filter((s: Service) => s.outgoing),
  serviceFiles: (state: State) => state.service_files,
  tracks: (state: State) => state.tracks,
  fileSelections: (state: State) => state.file_selections,
  currentFileSelection: (state: State) => state.current_file_selection,
  isSaving: (state: State) => state.is_saving,
  isInitializing: (state: State) => state.is_initializing,
  publishWhenDone: (state: State) => state.publish_when_done,
}
