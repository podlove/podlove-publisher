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
  yield takeEvery(auphonic.UPDATE_FILE_SELECTION, handleFileSelection)
  yield takeEvery(auphonic.SET_SERVICE_FILES, handleServiceFilesAvailable)
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

  yield put(auphonic.setProductions(productions))
  yield put(auphonic.setPresets(presets))
  yield put(
    auphonic.setServices([
      {
        uuid: 'url',
        display_name: 'From URL',
        email: '',
        incoming: true,
        outgoing: false,
        type: 'url',
      },
      {
        uuid: 'file',
        display_name: 'Upload from computer',
        email: '',
        incoming: true,
        outgoing: false,
        type: 'file',
      },
      ...services,
    ])
  )

  yield takeEvery(auphonic.CREATE_PRODUCTION, handleCreateProduction, auphonicApi)
  yield takeEvery(
    auphonic.CREATE_MULTITRACK_PRODUCTION,
    handleCreateMultitrackProduction,
    auphonicApi
  )
  yield takeEvery(auphonic.selectService, fetchServiceFiles, auphonicApi)
  yield takeEvery(auphonic.uploadFile, uploadFile, auphonicApi)
  yield takeEvery(auphonic.saveProduction, handleSaveProduction, auphonicApi)
}

function* handleSaveProduction(
  auphonicApi: AuphonicApiClient,
  action: { type: string; payload: any }
) {
  const uuid = action.payload.uuid
  const payload = action.payload.payload
  const { result } = yield auphonicApi.post(`production/${uuid}.json`, payload)
  console.log({ result })
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
  const preset: auphonic.Preset = yield select(selectors.auphonic.preset)
  const { result } = yield auphonicApi.post(`productions.json`, {
    preset: preset.uuid,
    metadata: { title: defaultTitle() },
  })
  const production = result.data

  yield put(auphonic.setProduction(production))
}

function* handleCreateMultitrackProduction(auphonicApi: AuphonicApiClient) {
  const preset: auphonic.Preset = yield select(selectors.auphonic.preset)
  const { result } = yield auphonicApi.post(`productions.json`, {
    preset: preset.uuid,
    metadata: { title: defaultTitle() },
    is_multitrack: true,
  })
  const production = result.data

  yield put(auphonic.setProduction(production))
}

function* handleServiceFilesAvailable(action: {
  type: string
  payload: { uuid: string; files: string[] }
}) {
  const currentKey: string = yield select(selectors.auphonic.currentFileSelection)

  // select first available file
  yield put(
    auphonic.updateFileSelection({
      key: currentKey,
      prop: 'fileSelection',
      value: action.payload.files[0],
    })
  )
}

function* handleFileSelection(action: {
  type: string
  payload: { key: string; prop: string; value: any }
}) {
  const { prop, value } = action.payload
  if (prop === 'currentServiceSelection') {
    yield put(auphonic.selectService(value))
  }
}

export default function () {
  return function* () {
    yield takeFirst(auphonic.INIT, auphonicSaga)
  }
}
