import { get } from 'lodash';
import { handleActions, } from 'redux-actions'
import { INIT, init } from './lifecycle.store';

export type State = {
  baseUrl: string | null;
  permissions: {
    canManageContributors: boolean;
    canUseAuphonic: boolean;
  }
  api: {
    nonce: string | null;
    base: string | null;
    auth: string | null;
    bearer: string | null;
  }
}

export const initialState: State = {
  baseUrl: null,
  permissions: {
    canManageContributors: false,
    canUseAuphonic: false,
  },
  api: {
    nonce: null,
    base: null,
    auth: null,
    bearer: null
  }
};

export const reducer = handleActions({
  [INIT]: (state: State, action: typeof init): State => ({
    ...state,
    baseUrl: get(action, ['payload', 'baseUrl'], null),
    permissions: {
      canUseAuphonic: get(action, ['payload', 'permissions', 'canUseAuphonic'], false),
      canManageContributors: get(
        action,
        ['payload', 'permissions', 'canManageContributors'],
        false
      ),
    },
    api: {
      base: get(action, ['payload', 'api', 'base'], null),
      nonce: get(action, ['payload', 'api', 'nonce'], null),
      auth: get(action, ['payload', 'api', 'auth'], null),
      bearer: get(action, ['payload', 'api', 'bearer'], null),
    }
  })
}, initialState);

export const selectors = {
  baseUrl: (state: State) => state.baseUrl,
  canManageContributors: (state: State) => state.permissions.canManageContributors,
  canUseAuphonic: (state: State) => state.permissions.canUseAuphonic,
  nonce: (state: State) => state.api.nonce,
  base: (state: State) => state.api.base,
  auth: (state: State) => state.api.auth,
  bearer: (state: State) => state.api.bearer,
}
