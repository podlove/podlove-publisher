import { get } from 'lodash'
import { createAction, handleActions } from 'redux-actions'

export const INIT = 'podlove/publisher/admin/INIT'
export const UPDATE = 'podlove/publisher/admin/UPDATE'

export type State = {
  auphonicWebhookConfig: {
    authkey: string | null
    enabled: boolean
  } | null
}

export const initialState: State = {
  auphonicWebhookConfig: null,
}

export const update = createAction<{ prop: string; value: any }>(UPDATE)
export const init = createAction<void>(INIT)

export const reducer = handleActions(
  {
    [UPDATE]: (state: State, action: typeof update): State => ({
      ...state,
      [get(action, ['payload', 'prop'])]: get(action, ['payload', 'value'], null),
    }),
  },
  initialState
)

export const selectors = {
  auphonicWebhookConfig: (state: State) => state.auphonicWebhookConfig,
}
