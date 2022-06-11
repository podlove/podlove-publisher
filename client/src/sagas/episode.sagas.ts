import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { fork, put, select, throttle } from 'redux-saga/effects'
import * as episode from '../store/episode.store'
import { createApi } from './api'
import { takeFirst } from './helper'

interface EpisodeData {
  number: string
  title: string
  subtitle: string
  summary: string
}

function* episodeSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)

  yield throttle(1000, episode.UPDATE, save, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)
  const { result }: { result: EpisodeData } = yield api.get(`episodes/${episodeId}`)

  if (result) {
    yield put(episode.set(result))
  }
}

function* save(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)

  const payload: EpisodeData = {
    number: yield select(selectors.episode.number),
    title: yield select(selectors.episode.title),
    subtitle: yield select(selectors.episode.subtitle),
    summary: yield select(selectors.episode.summary),
  }

  yield api.post(`episodes/${episodeId}`, payload)
}

export default function () {
  return function* () {
    yield takeFirst(episode.INIT, episodeSaga)
  }
}
