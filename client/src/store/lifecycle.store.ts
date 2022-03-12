import { handleActions, createAction } from 'redux-actions'

export type State = {
  saved: boolean;
  changes: boolean;
  bootstrapped: boolean;
}

export const INIT = 'podlove/publisher/INIT'
export const READY = 'podlove/publisher/READY'
export const SAVE = 'podlove/publisher/SAVE'
export const ERROR = 'podlove/publisher/ERROR'

export const init = createAction<{
  api?: {
    base: string;
    nonce: string;
  },
  post?: {
    id: string;
  },
  episode?: {
    id: string;
    duration?: string;
  }

}>(INIT);

export const save = createAction<void>(SAVE)
export const error = createAction<any>(ERROR)
export const ready = createAction<void>(READY)

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
