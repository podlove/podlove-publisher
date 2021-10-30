import { get } from 'lodash';
import { handleActions } from 'redux-actions'
import Timestamp from '@lib/timestamp'
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
    duration: Timestamp.fromString(get(action, ['payload', 'episode', 'duration'], null)).totalMs
  })
}, initialState);

export const selectors = {
  duration: (state: State) => state.duration
}
