import * as auphonic from '@store/auphonic.store'
import { channel, takeFirst } from '../sagas/helper'
import { select, takeEvery, call, put, fork } from 'redux-saga/effects'
import { createApi } from '../sagas/api'
import { PodloveApiClient } from '@lib/api'

function* auphonicSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const { result }: { result: string } = yield api.get(`auphonic/token`)

  if (result) {
    yield put(auphonic.setToken(result))
  }
}

export default function () {
  return function* () {
    yield takeFirst(auphonic.INIT, auphonicSaga)
  }
}
