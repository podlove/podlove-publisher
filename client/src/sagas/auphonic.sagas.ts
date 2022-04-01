import * as auphonic from '@store/auphonic.store'
import { takeFirst } from '../sagas/helper'
import { put, fork, takeEvery } from 'redux-saga/effects'
import { createApi } from '../sagas/api'
import { createApi as createAuphonicApi } from '../sagas/auphonic.api'
import { PodloveApiClient } from '@lib/api'
import { AuphonicApiClient } from '@lib/auphonic.api'

function* auphonicSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const { result }: { result: string } = yield api.get(`auphonic/token`)

  if (result) {
    yield put(auphonic.setToken(result))
    yield fork(initializeAuphonicApi)
  }
}

function* initializeAuphonicApi() {
  const auphonicApi: AuphonicApiClient = yield createAuphonicApi()
  const {
    result: { data: presets },
  } = yield auphonicApi.get(`presets.json`)
  const {
    result: { data: productions },
  } = yield auphonicApi.get(`productions.json`, { limit: 10, minimal_data: true })
  console.log('auphonic', { presets, productions })

  yield put(auphonic.setProductions(productions))

  yield takeEvery(auphonic.CREATE_PRODUCTION, handleCreateProduction, auphonicApi)
  yield takeEvery(
    auphonic.CREATE_MULTITRACK_PRODUCTION,
    handleCreateMultitrackProduction,
    auphonicApi
  )
}

function defaultTitle() {
  return `Audio Production ${new Date().toLocaleString()}`
}

function* handleCreateProduction(auphonicApi: AuphonicApiClient) {
  const { result } = yield auphonicApi.post(`productions.json`, {
    metadata: { title: defaultTitle() },
  })
  const production = result.data

  yield put(auphonic.setProduction(production))
}

function* handleCreateMultitrackProduction(auphonicApi: AuphonicApiClient) {
  const { result } = yield auphonicApi.post(`productions.json`, {
    metadata: { title: defaultTitle() },
    is_multitrack: true,
  })
  const production = result.data

  yield put(auphonic.setProduction(production))
}

export default function () {
  return function* () {
    yield takeFirst(auphonic.INIT, auphonicSaga)
  }
}
