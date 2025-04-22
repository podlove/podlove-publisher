import * as plus from '@store/plus.store'
import { takeFirst } from './helper'
import { fork, put, select, call, takeEvery } from 'redux-saga/effects'
import { PodloveApiClient } from '@lib/api'
import { createApi } from './api'

function* plusSaga() {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const { result } = yield api.get(`admin/plus/features`)

  yield put(plus.setFeature({ feature: 'fileStorage', value: result.file_storage }))
  yield put(plus.setFeature({ feature: 'feedProxy', value: result.feed_proxy }))

  yield takeEvery(plus.SET_FEATURE, setFeature, api)
}

function* setFeature(api: PodloveApiClient, action: ReturnType<typeof plus.setFeature>) {
  const { feature, value } = action.payload
  yield api.post(`admin/plus/set_feature`, { feature, value })
}

export default function () {
  return function* () {
    yield takeFirst(plus.INIT, plusSaga)
  }
}
