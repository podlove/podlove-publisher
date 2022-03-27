import { createAction, handleActions } from 'redux-actions'

export type State = {
  token: string | null
  productionId: string | null
}

export const initialState: State = {
  token: null,
  productionId: null,
}

export const INIT = 'podlove/publisher/auphonic/INIT'
export const SET_TOKEN = 'podlove/publisher/auphonic/SET_TOKEN'
export const SET_PRODUCTION = 'podlove/publisher/auphonic/SET_PRODUCTION'

export const init = createAction<void>(INIT)
export const setToken = createAction<string>(SET_TOKEN)
export const setProduction = createAction<string>(SET_PRODUCTION)

export const reducer = handleActions(
  {
    [SET_PRODUCTION]: (state: State, action: { payload: string | null }): State => ({
      ...state,
      productionId: action.payload,
    }),
    [SET_TOKEN]: (state: State, action: { payload: string | null }): State => ({
      ...state,
      token: action.payload,
    }),
  },
  initialState
)

export const selectors = {
  token: (state: State) => state.token,
  productionId: (state: State) => state.productionId,
}
