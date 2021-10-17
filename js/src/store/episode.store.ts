import { get } from 'lodash';
import { handleActions } from 'redux-actions'
import { init, INIT } from './lifecycle.store';

export type State = {
  duration: string | null
}
export const initialState: State = {
  duration: null
};

export const reducer = handleActions({
  [INIT]: (state: State, action: typeof init): State => ({
    ...state,
    duration: get(action, ['episode', 'duration'], null)
  })
}, initialState);

export const selectors = {
  duration: (state: State) => state.duration
}
