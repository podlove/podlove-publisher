import { handleActions, createAction } from "redux-actions";
import { PodloveEpisodeList } from "../types/relatedEpisodes.types";
import * as lifecycle from './lifecycle.store'

export type State = {
  episodeList: PodloveEpisodeList[]
  selectEpisodes: Number[]
}

export const initialState: State = {
  episodeList: [],
  selectEpisodes: []
}

export const INIT = 'podlove/publisher/relatedEpisodes/INIT'
export const SET_EPISODE_LIST = 'podlove/publisher/relatedEpisodes/SET_EPISODE_LIST'
export const SET_SELECTED_EPISODES = 'podlove/publisher/relatedEpisodes/SET_SELECTED_EPISODES'
export const UPDATE_RELATED_EPISODES = 'podlove/publisher/relatedEpisodes/UPDATE_RELATED_EPISODES'

export const init = createAction<void>(INIT);
export const setEpisodeList = createAction<PodloveEpisodeList[]>(SET_EPISODE_LIST);
export const setSelectedEpisodes = createAction<Number[]>(SET_SELECTED_EPISODES);
export const updateRelatedEpisodes = createAction<void>(UPDATE_RELATED_EPISODES);

export const reducer = handleActions(
  {
    [lifecycle.INIT]: (state: State, action: typeof lifecycle.init): State => ({
      ...state,

    }),
    [SET_EPISODE_LIST]: (state: State, action: { payload: PodloveEpisodeList[] }): State => ({
      ...state,
      episodeList: action.payload,
    }),
    [SET_SELECTED_EPISODES]: (state: State, action: { payload: Number[]}): State => ({
      ...state,
      selectEpisodes: action.payload,
    }),
  }, initialState
)

export const selectors = {
  episodeList: (state: State) => state.episodeList,
  selectEpisodes: (state: State) => state.selectEpisodes,
}
