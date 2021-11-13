import { handleActions, createAction } from 'redux-actions'

import { PodloveContributor } from '@types/contributors.types';


export type State = PodloveContributor[]

export const initialState: State = [];

export const SET = 'podlove/publisher/contributors/SET'

export const set = createAction<PodloveContributor[]>(SET);

export const reducer = handleActions({
  [SET]: (state: State, action: typeof set): State => action.payload
}, initialState);

export const selectors = {
  list: (state: State) => state
}
