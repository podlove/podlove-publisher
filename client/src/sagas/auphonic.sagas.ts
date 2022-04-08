import * as auphonic from '@store/auphonic.store'
import { takeFirst } from '../sagas/helper'
import { put, fork, takeEvery, select } from 'redux-saga/effects'
import { createApi } from '../sagas/api'
import { createApi as createAuphonicApi } from '../sagas/auphonic.api'
import { PodloveApiClient } from '@lib/api'
import { AuphonicApiClient } from '@lib/auphonic.api'
import { selectors } from '@store'

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

  let {
    result: { data: services },
  } = yield auphonicApi.get(`services.json`)

  services.unshift({
    uuid: 'url',
    display_name: 'From URL',
    email: '',
    incoming: true,
    outgoing: false,
    type: 'url',
  })

  services.unshift({
    uuid: 'file',
    display_name: 'Upload from computer',
    email: '',
    incoming: true,
    outgoing: false,
    type: 'file',
  })

  yield put(auphonic.setProductions(productions))
  yield put(auphonic.setServices(services))

  yield takeEvery(auphonic.CREATE_PRODUCTION, handleCreateProduction, auphonicApi)
  yield takeEvery(
    auphonic.CREATE_MULTITRACK_PRODUCTION,
    handleCreateMultitrackProduction,
    auphonicApi
  )
  yield takeEvery(auphonic.selectService, fetchServiceFiles, auphonicApi)
  yield takeEvery(auphonic.uploadFile, uploadFile, auphonicApi)
}

function* uploadFile(auphonicApi: AuphonicApiClient, action: { type: string; payload: File }) {
  const uuid: string = yield select(selectors.auphonic.productionId)
  yield auphonicApi.upload(`production/${uuid}/upload.json`, action.payload)
}

function* fetchServiceFiles(
  auphonicApi: AuphonicApiClient,
  action: { type: string; payload: string }
) {
  const uuid = action.payload

  if (uuid == 'file' || uuid == 'url') {
    return
  }

  const { result } = yield auphonicApi.get(`service/${uuid}/ls.json`)

  yield put(auphonic.setServiceFiles({ uuid, files: result.data }))
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
