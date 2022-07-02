import { PodloveApiClient } from '@lib/api'
import { fork, put } from 'redux-saga/effects'
import { takeFirst } from './helper'
import * as lifecycle from '../store/lifecycle.store'
import * as podcast from '../store/podcast.store'
import { createApi } from './api'

interface PodcastData {
  title: string | null
  subtitle: string | null
  summary: string | null
  mnemonic: string | null
  itunes_type: string | null
  author_name: string | null
  poster: string | null
  link: string | null
}

function* podcastSaga() {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const { result }: { result: PodcastData } = yield api.get(`podcast`)

  if (result) {
    yield put(podcast.set(result))
  }
}

export default function () {
  return function* () {
    yield takeFirst(lifecycle.INIT, podcastSaga)
  }
}
