import { get } from 'lodash';
import { handleActions, } from 'redux-actions'
import { INIT, init } from './lifecycle.store';

export type State = {
  api: {
    nonce: string;
    base: string;
  }
}

export const initialState: State = {
  api: {
    nonce: null,
    base: null
  }
};

export const reducer = handleActions({
  [INIT]: (state: State, action: typeof init): State => ({
    ...state,
    api: {
      base: get(action, ['payload', 'api', 'base'], null),
      nonce: get(action, ['payload', 'api', 'nonce'], null)
    }
  })
}, initialState);

export const selectors = {
  nonce: (state: State) => state.api.nonce,
  base: (state: State) => state.api.base
}
