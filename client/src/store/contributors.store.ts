import { handleActions, createAction } from 'redux-actions'
import { PodloveContributor, PodloveGroup, PodloveRole } from '../types/contributors.types';

export type State = {
  contributors: PodloveContributor[],
  roles: PodloveRole[],
  groups: PodloveGroup[]
}

export const initialState: State = {
  contributors: [],
  roles: [],
  groups: [],
};

export const INIT = 'podlove/publisher/contributors/INIT'
export const SET_CONTRIBUTORS = 'podlove/publisher/contributors/SET_CONTRIBUTORS'
export const SET_ROLES = 'podlove/publisher/contributors/SET_ROLES'
export const SET_GROUPS = 'podlove/publisher/contributors/SET_GROUPS'
export const ADD_CONTRIBUTOR = 'podlove/publisher/contributors/ADD'

export const init = createAction<void>(INIT);
export const setContributors = createAction<PodloveContributor[]>(SET_CONTRIBUTORS);
export const setRoles = createAction<PodloveRole[]>(SET_ROLES);
export const setGroups = createAction<PodloveGroup[]>(SET_GROUPS);
export const addContributor = createAction<Partial<PodloveContributor>>(ADD_CONTRIBUTOR);

export const reducer = handleActions({
  [SET_CONTRIBUTORS]: (state: State, action: { payload: PodloveContributor[] }): State => ({
    ...state,
    contributors: action.payload
  }),
  [SET_ROLES]: (state: State, action: { payload: PodloveRole[] }): State => ({
    ...state,
    roles: action.payload
  }),
  [SET_GROUPS]: (state: State, action: { payload: PodloveGroup[] }): State => ({
    ...state,
    groups: action.payload
  }),
  [ADD_CONTRIBUTOR]: (state: State, action: { payload: PodloveContributor }): State => ({
    ...state,
    contributors: [
      ...state.contributors,
      action.payload
    ]
  })
}, initialState);

export const selectors = {
  contributors: (state: State) => state.contributors,
  roles: (state: State) => state.roles,
  groups: (state: State) => state.groups,
}
