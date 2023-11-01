import { createApi } from './api'
import { PodloveApiClient } from '@lib/api'
import { fork, takeEvery } from '@redux-saga/core/effects'
import { select, put } from 'redux-saga/effects'
import { get } from 'lodash'
import { selectors } from '@store'
import { PodloveEpisodeList } from '../types/relatedEpisodes.types'
import { takeFirst } from './helper'
import * as relatedEpisodesStore from '@store/relatedEpisodes.store'

function* relatedEpisodesSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()

  yield fork(initialize, apiClient)
  yield takeEvery(relatedEpisodesStore.SET_SELECTED_EPISODES, save, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)

  const [relatedEpisodes, episodeList ]: [
    { result: Number[] },
    { result: PodloveEpisodeList[] }
  ] = yield Promise.all([
    api.get(`episodes/${episodeId}/related?status=all`),
    api.get('episodes?status=all&sort_by=post_id&order_by=asc')
  ])

  const related = get(relatedEpisodes, ['result', 'relatedEpisodes'], [])
  const episodes = get(episodeList, ['result', 'results'], [])

  const arr = related.map( (r : any) => (r.related_episode_id))

  yield put(relatedEpisodesStore.setSelectedEpisodes(arr))
  yield put(relatedEpisodesStore.setEpisodeList(episodes))
}

function* save(
  api: PodloveApiClient, 
  action: {type: string}
) {
  const episodeId: string = yield select(selectors.episode.id)
  const selectEpisodes: Number[] = yield select(selectors.relatedEpisodes.selectEpisode)

  yield api.post(`episodes/${episodeId}/related`, {related: selectEpisodes})
}

export default function () {
  return function* () {
    yield takeFirst(relatedEpisodesStore.INIT, relatedEpisodesSaga)
  }
}