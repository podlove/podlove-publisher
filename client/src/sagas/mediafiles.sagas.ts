import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { fork, put, select } from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import { MediaFile } from '@store/mediafiles.store'
import { takeFirst } from './helper'
import { createApi } from './api'

function* mediafilesSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)
  const {
    result: { results: files },
  }: { result: { results: MediaFile[] } } = yield api.get(`episodes/${episodeId}/media`)

  console.table(files)

  if (files) {
    yield put(mediafiles.set(files))
  }

  yield put(mediafiles.initDone())
}

export default function () {
  return function* () {
    yield takeFirst(mediafiles.INIT, mediafilesSaga)
  }
}
