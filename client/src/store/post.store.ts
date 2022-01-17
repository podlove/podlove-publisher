import { get } from 'lodash';
import { handleActions, } from 'redux-actions'
import { INIT, init } from './lifecycle.store';

export type State = {
  id: string;
}

export const initialState: State = {
  id: null
};

export const reducer = handleActions({
  [INIT]: (state: State, action: typeof init): State => ({
    ...state,
    id: get(action, ['payload', 'post', 'id'], null)
  })
}, initialState);

export const selectors = {
  id: (state: State) => state.id
}
