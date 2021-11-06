import { get } from 'lodash';
import { handleActions, createAction } from 'redux-actions'

import { PodloveTranscript } from '@types/transcripts.types';

export const SET = 'podlove/publisher/transcript/SET'
export const set = createAction<PodloveTranscript[]>(SET)

export type State = {
  transcripts: PodloveTranscript[];
}

export const initialState: State = {
  transcripts: []
};

export const reducer = handleActions({
  [SET]: (state: State, action: typeof set): State => ({
    ...state,
    transcripts: action.payload
  })
}, initialState);

export const selectors = {
  transcripts: (state: State) => state.transcripts
}
