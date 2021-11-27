import { get } from 'lodash';
import { handleActions } from 'redux-actions'
<<<<<<< HEAD
import Timestamp from '@lib/timestamp'
import { init, INIT } from './lifecycle.store';

export type State = {
  id: string | null;
  duration: string | null;
}

export const initialState: State = {
  id: null,
=======
import { init, INIT } from './lifecycle.store';

export type State = {
  duration: string | null
}
export const initialState: State = {
>>>>>>> 6ca060a4744249c97d016dd3c3b420a4285881e3
  duration: null
};

export const reducer = handleActions({
  [INIT]: (state: State, action: typeof init): State => ({
    ...state,
<<<<<<< HEAD
    id: get(action, ['payload', 'episode', 'id'], null),
    duration: Timestamp.fromString(get(action, ['payload', 'episode', 'duration'], null)).totalMs
=======
    duration: get(action, ['episode', 'duration'], null)
>>>>>>> 6ca060a4744249c97d016dd3c3b420a4285881e3
  })
}, initialState);

export const selectors = {
<<<<<<< HEAD
  id: (state: State) => state.id,
  duration: (state: State) => state.duration,
=======
  duration: (state: State) => state.duration
>>>>>>> 6ca060a4744249c97d016dd3c3b420a4285881e3
}
