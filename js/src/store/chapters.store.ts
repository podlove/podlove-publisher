import { get } from 'lodash'
import { handleActions, createAction } from 'redux-actions'
import { PodloveChapter } from '@types/chapters.types'
import { INIT, init } from './lifecycle.store';

export type State = {
  chapters: PodloveChapter[];
  selected: number | null;
}

export const initialState: State = {
  chapters: [],
  selected: null
};

export const UPDATE = 'podlove/publisher/chapter/UPDATE';
export const SELECT = 'podlove/publisher/chapter/SELECT';

export const update = createAction<{ chapter: PodloveChapter; index: number; }>(UPDATE);
export const select = createAction<number>(SELECT);

export const reducer = handleActions({
  [INIT]: (state: State, action: typeof init): State => ({
    ...state,
    chapters: get(action, ['payload', 'chapters'], [])
  }),
  [UPDATE]: (state: State, action: typeof update): State => ({
    ...state,
    chapters: state.chapters.reduce((result: PodloveChapter[], chapter, chapterIndex) => [
      ...result,
      (chapterIndex === action.index ? action.chapter : chapter)
    ], [])
  }),
  [SELECT]: (state: State, action: typeof select): State => ({
    ...state,
    selected: action.payload
  }),

}, initialState);

export const selectors = {
  chapters: (state: State) => state.chapters,
  selected: (state: State) => state.selected !== null ? get(state, ['chapters', state.selected], null) : null
}
