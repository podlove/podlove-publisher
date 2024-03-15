import { get } from 'lodash'
import { handleActions, createAction } from 'redux-actions'

export const INIT = 'podlove/publisher/admin/INIT'
export const SET = 'podlove/publisher/admin/SET'
export const UPDATE_TYPE = 'podlove/publisher/admin/UPDATE_TYPE'

export type State = {
    bannerHide: boolean | null,
    type: string | null,
    feedUrl: string | null
}

export const initialState: State = {
    bannerHide: null,
    type: null,
    feedUrl: null
}

export const init = createAction<void>(INIT)
export const set = createAction<Partial<State>>(SET)
export const update_type = createAction<string>(UPDATE_TYPE)

export const reducer = handleActions(
    {
        [SET]: (state: State, action: { payload: Partial<State> }): State => ({
            bannerHide: get(action, ['payload', 'banner_hide'], state.bannerHide),
            type: get(action, ['payload', 'type'], state.type),
            feedUrl: get(action, ['payload', 'feedUrl'], state.feedUrl),
        }),
        [UPDATE_TYPE]: (state: State, action: { payload: string }): State => ({
            ...state,
            type: action.payload
        }),
    },
    initialState
)

export const selectors = {
    bannerHide: (state: State) => state.bannerHide,
    type: (state: State) => state.type,
    feedUrl: (state: State) => state.feedUrl,
}
