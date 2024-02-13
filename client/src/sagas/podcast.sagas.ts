import { PodloveApiClient } from '@lib/api'
import { fork, put, takeEvery } from 'redux-saga/effects'
import { takeFirst } from './helper'
import * as lifecycle from '../store/lifecycle.store'
import * as podcast from '../store/podcast.store'
import { createApi } from './api'
import { Action } from 'redux'
import { get, isEmpty } from 'lodash'

interface PodcastData {
  title: string | null
  subtitle: string | null
  summary: string | null
  mnemonic: string | null
  itunes_type: string | null
  author_name: string | null
  poster: string | null
  link: string | null
  license_name: string | null
  license_url: string | null
}

let PODCAST_UPDATE: { [key: string]: any } = {}

function* podcastSaga() {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)

  yield takeEvery(podcast.UPDATE, collectPodcastUpdate)
}

function* initialize(api: PodloveApiClient) {
  const { result }: { result: PodcastData } = yield api.get(`podcast`)

  if (result) {
    yield put(podcast.set(result))
  }
}

function collectPodcastUpdate(action: Action) {
  const prop = get(action, ['payload', 'prop'])
  const value = get(action, ['payload', 'value'], null)

  if (!prop) {
    return
  }

  PODCAST_UPDATE[prop] = value
}

function* save(api: PodloveApiClient, action: Action) {
  if (isEmpty(PODCAST_UPDATE)) {
    return
  }

  yield api.put(`podcast/`, PODCAST_UPDATE)
  yield put(podcast.saved(PODCAST_UPDATE))

  PODCAST_UPDATE = {}
}

export default function () {
  return function* () {
    yield takeFirst(lifecycle.INIT, podcastSaga)
  }
}
