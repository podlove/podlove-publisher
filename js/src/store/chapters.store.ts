import { get } from 'lodash'
import { handleActions, createAction } from 'redux-actions'
import { PodloveChapter } from '@types/chapters.types'
import { INIT, init } from './lifecycle.store';

export type State = {
  chapters: PodloveChapter[];
  form: {
    visible: boolean;
    title: string;
    url: string;
    start: string;
  }
}

export const initialState: State = {
  chapters: [],
  form: {
    visible: false,
    title: null,
    url: null,
    start: null
  }
};

export const UPDATE = 'podlove/publisher/chapter/UPDATE';

export const update = createAction<PodloveChapter>(UPDATE);

export const reducer = handleActions({
  [INIT]: (state: State, action: typeof init): State => ({
    ...state,
    chapters: get(action, ['payload', 'chapters'], [])
  }),
  [UPDATE]: (state: State, action: typeof update): State => ({
    ...state,
    form: action.payload
  })
}, initialState);

export const selectors = {
  chapters: (state: State) => state.chapters
}
