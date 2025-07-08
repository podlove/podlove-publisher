import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import {
  fork,
  put,
  select,
  takeEvery,
  takeLatest,
  debounce,
  throttle,
} from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import * as episode from '@store/episode.store'
import * as wordpress from '@store/wordpress.store'
import { MediaFile } from '@store/mediafiles.store'
import { takeFirst } from './helper'
import { createApi } from './api'

// Import handlers from other saga modules
import {
  handleEnable,
  handleDisable
} from './mediafiles.enable.sagas'
import {
  handleVerify,
  verifyAll
} from './mediafiles.verification.sagas'
import {
  maybeUpdateDuration
} from './mediafiles.duration.sagas'
import {
  maybeUpdateSlug,
  updateSelectedFileNames
} from './mediafiles.slug.sagas'
import {
  handleFileSelection
} from './mediafiles.fileselection.sagas'
import {
  selectMediaFromLibrary,
  triggerPlusUpload,
  setUploadMedia
} from './mediafiles.upload.sagas'

function* mediafilesSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)

  const {
    result: { results: files },
  }: { result: { results: MediaFile[] } } = yield api.get(`episodes/${episodeId}/media`)

  if (files) {
    yield put(mediafiles.set(files))
  }

  yield takeEvery(mediafiles.ENABLE, handleEnable, api)
  yield takeEvery(mediafiles.DISABLE, handleDisable, api)
  yield takeEvery(mediafiles.VERIFY, handleVerify, api)
  yield takeLatest(episode.SLUG_CHANGED, verifyAll, api)
  yield takeLatest(episode.SLUG_CHANGED, updateSelectedFileNames, api)
  yield debounce(2000, wordpress.UPDATE, maybeUpdateSlug, api)
  yield takeEvery(mediafiles.FILE_SELECTED, handleFileSelection, api)

  yield throttle(
    2000,
    [mediafiles.ENABLE, mediafiles.DISABLE, mediafiles.UPDATE],
    maybeUpdateDuration,
    api
  )
  yield takeEvery(mediafiles.UPLOAD_INTENT, selectMediaFromLibrary)
  yield takeEvery(mediafiles.PLUS_UPLOAD_INTENT, triggerPlusUpload, api)
  yield takeEvery(mediafiles.SET_UPLOAD_URL, setUploadMedia, api)

  yield put(mediafiles.initDone())
}

export default function () {
  return function* () {
    yield takeFirst(mediafiles.INIT, mediafilesSaga)
  }
}
