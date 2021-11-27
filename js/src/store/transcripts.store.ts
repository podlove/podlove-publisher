import { handleActions, createAction } from 'redux-actions'

import { PodloveTranscript, PodloveTranscriptVoice } from '@types/transcripts.types';

export const SET_TRANSCRIPTS = 'podlove/publisher/transcript/SET_TRANSCRIPTS'
export const SET_VOICES = 'podlove/publisher/transcript/SET_VOICES'
export const setTranscripts = createAction<PodloveTranscript[]>(SET_TRANSCRIPTS)
export const setVoices = createAction<PodloveTranscriptVoice[]>(SET_VOICES)

export type State = {
  transcripts: PodloveTranscript[];
  voices: PodloveTranscriptVoice[];
}

export const initialState: State = {
  transcripts: [],
  voices: []
};

export const reducer = handleActions({
  [SET_TRANSCRIPTS]: (state: State, action: typeof setTranscripts): State => ({
    ...state,
    transcripts: action.payload
  }),
  [SET_VOICES]: (state: State, action: typeof setVoices): State => ({
    ...state,
    voices: action.payload
  }),
}, initialState);

export const selectors = {
  transcripts: (state: State) => state.transcripts,
  voices: (state: State) => state.voices,
}
