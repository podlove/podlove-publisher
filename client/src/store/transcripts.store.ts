import { handleActions, createAction } from 'redux-actions'

import { PodloveTranscript, PodloveTranscriptVoice } from '../types/transcripts.types'

export const INIT = 'podlove/publisher/transcript/INIT'
export const SET_TRANSCRIPTS = 'podlove/publisher/transcript/SET_TRANSCRIPTS'
export const SET_VOICES = 'podlove/publisher/transcript/SET_VOICES'
export const UPDATE_VOICE = 'podlove/publisher/transcript/UPDATE_VOICE'
export const IMPORT_TRANSCRIPTS = 'podlove/publisher/transcript/IMPORT_TRANSCRIPTS'
export const IMPORT_ASSET_TRANSCRIPTS = 'podlove/publisher/transcript/IMPORT_ASSET_TRANSCRIPTS'
export const DELETE_TRANSCRIPTS = 'podlove/publisher/transcript/DELETE_TRANSCRIPTS'

export const init = createAction<void>(INIT)
export const setTranscripts = createAction<PodloveTranscript[]>(SET_TRANSCRIPTS)
export const setVoices = createAction<PodloveTranscriptVoice[]>(SET_VOICES)
export const updateVoice = createAction<{ voice: string; contributor: string }>(UPDATE_VOICE)
export const importTranscripts = createAction<string>(IMPORT_TRANSCRIPTS)
export const importTranscriptFromAsset = createAction<void>(IMPORT_ASSET_TRANSCRIPTS)
export const deleteTranscripts = createAction<void>(DELETE_TRANSCRIPTS)

export type State = {
  transcripts: PodloveTranscript[]
  voices: { voice: string, contributor: string }[]
}

export const initialState: State = {
  transcripts: [],
  voices: [],
}

export const reducer = handleActions(
  {
    [SET_TRANSCRIPTS]: (state: State, action: { payload: PodloveTranscript[] }): State => ({
      ...state,
      transcripts: action.payload,
    }),
    [SET_VOICES]: (state: State, action: { payload: PodloveTranscriptVoice[] }): State => ({
      ...state,
      voices: action.payload.map((elem: { voice: string; contributor_id: string }) => ({
        voice: elem.voice,
        contributor: elem.contributor_id,
      })),
    }),
    [UPDATE_VOICE]: (state: State, action: { payload: { voice: string; contributor: string } }): State => ({
      ...state,
      voices: state.voices.map((voice) => {
        if (voice.voice === action.payload.voice) {
          return {
            ...voice,
            contributor: action.payload.contributor,
          }
        }

        return voice
      }),
    }),
  },
  initialState
)

export const selectors = {
  transcripts: (state: State) => state.transcripts,
  voices: (state: State) => state.voices,
}
