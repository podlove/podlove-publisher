import { handleActions, createAction } from 'redux-actions'
import { PodloveContributor, PodloveGroup, PodloveRole } from '../types/contributors.types'

export type State = {
  contributors: PodloveContributor[],
  episodeContributors: PodloveContributor[],
  groups: PodloveGroup[],
  roles: PodloveRole[],
}

export const initialState: State = {
  contributors: [],
  episodeContributors: [],
  groups: [],
  roles: [],
}

export const INIT = 'podlove/publisher/contributors/INIT'
export const INIT_EPISODE_CONTRIBUTORS = 'podlove/publisher/contributors/INIT_EPISODE_CONTRIBUTORS'
export const INIT_GROUPS = 'podlove/publisher/contributors/INIT_GROUPS'
export const INIT_ROLES = 'podlove/publisher/contributors/INIT_ROLES'
export const SET = 'podlove/publisher/contributors/SET'
export const SET_EPISODE_CONTRIBUTORS = 'podlove/publisher/contributors/SET_EPISODE_CONTRIBUTORS'
export const SET_GROUPS = 'podlove/publisher/contributors/SET_GROUPS'
export const SET_ROLES = 'podlove/publisher/contributors/SET_ROLES'

export const init = createAction<void>(INIT)
export const initEpisodeContributors = createAction<void>(INIT_EPISODE_CONTRIBUTORS)
export const initGroups = createAction<void>(INIT_GROUPS)
export const initRoles = createAction<void>(INIT_ROLES)
export const set = createAction<PodloveContributor[]>(SET)
export const setEpisodeContributors = createAction<PodloveContributor[]>(SET_EPISODE_CONTRIBUTORS)
export const setGroups = createAction<PodloveGroup[]>(SET_GROUPS)
export const setRoles = createAction<PodloveGroup[]>(SET_ROLES)

export const reducer = handleActions({
  [SET]: (state: State, action: { payload: PodloveContributor[] }): State => ({
    ...state,
    contributors: action.payload,
  }),
  [SET_EPISODE_CONTRIBUTORS]: (state: State, action: { payload: PodloveContributor[] }): State => ({
    ...state,
    episodeContributors: action.payload,
  }),
  [SET_GROUPS]: (state: State, action: { payload: PodloveGroup[] }): State => ({
    ...state,
    groups: action.payload,
  }),
  [SET_ROLES]: (state: State, action: { payload: PodloveRole[] }): State => ({
    ...state,
    roles: action.payload,
  }),
}, initialState)

export const selectors = {
  list: (state: State) => state.contributors,
  episodeContributors: (state: State) => state.episodeContributors,
  groups: (state: State) => state.groups,
  roles: (state: State) => state.roles,
}
