import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { get } from 'lodash'
import { Action } from 'redux'
import { call, fork, put, select, takeEvery, throttle } from 'redux-saga/effects'
import * as episode from '../store/episode.store'
import * as wordpress from '../store/wordpress.store'
import { createApi } from './api'
import { takeFirst, waitFor } from './helper'

interface EpisodeData {
  number: string
  title: string
  subtitle: string
  summary: string
  poster: string
}

function* episodeSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)

  yield throttle(3000, episode.UPDATE, save, apiClient)
  yield takeEvery(episode.SELECT_POSTER, selectImageFromLibrary)
  yield takeEvery(episode.SET_POSTER, updatePoster)
}

function* initialize(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)
  const { result }: { result: EpisodeData } = yield api.get(`episodes/${episodeId}`)

  if (result) {
    yield put(episode.set(result))
  }
}

function* save(api: PodloveApiClient, action: Action) {
  const episodeId: string = yield select(selectors.episode.id)
  const prop = get(action, ['payload', 'prop'])
  const value = get(action, ['payload', 'value'], null)

  if (!prop) {
    return
  }

  yield api.put(`episodes/${episodeId}`, { [prop]: value })
}

function* selectImageFromLibrary() {
  yield put(wordpress.selectImageFromLibrary({ onSuccess: { type: episode.SET_POSTER } }))
}

function* updatePoster(action: Action) {
  yield put(episode.update({ prop: 'episode_poster', value: get(action, ['payload']) }))
}

export default function () {
  return function* () {
    yield takeFirst(episode.INIT, episodeSaga)
  }
}
