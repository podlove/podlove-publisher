import { handleActions, createAction } from 'redux-actions'

export const NOTIFY = 'podlove/publisher/NOTIFY'
export const notify = createAction<{ type: 'success' | 'warning' | 'error', message: string; }>(NOTIFY)

export type State = {
}

export const initialState: State = {
};

export const reducer = handleActions({
}, initialState);

export const selectors = {
}
