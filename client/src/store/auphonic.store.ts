import { createAction, handleActions } from 'redux-actions'

export type State = {
  productionId: string | null
}

export const initialState: State = {
  productionId: null,
}

export const INIT = 'podlove/publisher/auphonic/INIT'
export const SET_PRODUCTION = 'podlove/publisher/auphonic/SET_PRODUCTION'

export const init = createAction<void>(INIT)
export const setProduction = createAction<string>(SET_PRODUCTION)

export const reducer = handleActions(
  {
    [SET_PRODUCTION]: (state: State, action: { payload: string | null }): State => ({
      ...state,
      productionId: action.payload,
    }),
  },
  initialState
)

export const selectors = {
  productionId: (state: State) => state.productionId,
}
