import { handleActions, createAction } from 'redux-actions'
import { PodloveChapter } from '@types/chapters.types';

export type State = {
  saved: boolean;
  changes: boolean;
  bootstrapped: boolean;
}

export const INIT = 'podlove/publisher/INIT';

export const init = createAction<{
  chapters?: PodloveChapter[]
}>(INIT);

export const initialState: State = {
  saved: false,
  changes: false,
  bootstrapped: false
};

export const reducer = handleActions({
  [INIT]: (state: State): State => ({
    ...state,
    bootstrapped: true
  })
}, initialState);

export const selectors = {
  bootstrapped: (state: State) => state.bootstrapped
}
