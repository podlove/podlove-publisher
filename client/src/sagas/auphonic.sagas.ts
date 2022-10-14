import * as auphonic from '@store/auphonic.store'
import * as episode from '@store/episode.store'
import { takeFirst } from '../sagas/helper'
import { delay, put, take, fork, takeEvery, select, all, call, race } from 'redux-saga/effects'
import { createApi } from '../sagas/api'
import { createApi as createAuphonicApi } from '../sagas/auphonic.api'
import { PodloveApiClient } from '@lib/api'
import { AuphonicApiClient } from '@lib/auphonic.api'
import { selectors } from '@store'
import { v4 as uuidv4 } from 'uuid'

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

    yield takeEvery(auphonic.SET_PRODUCTION, initializeWebhookConfig, api)
    yield takeEvery(auphonic.UPDATE_WEBHOOK, updateWebhookConfig, api)

    yield takeEvery(auphonic.SET_PRODUCTION, memorizeSelectedProduction, api)
    yield takeEvery(auphonic.DESELECT_PRODUCTION, forgetSelectedProduction, api)
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

  yield call(maybeRestoreProductionSelection)
  yield put(auphonic.initDone())

  yield takeEvery(auphonic.CREATE_PRODUCTION, handleCreateProduction, auphonicApi)
  yield takeEvery(
    auphonic.CREATE_MULTITRACK_PRODUCTION,
    handleCreateMultitrackProduction,
    auphonicApi
  )
  yield takeEvery(auphonic.selectService, fetchServiceFiles, auphonicApi)
  yield takeEvery(auphonic.saveProduction, handleSaveProduction, auphonicApi)
  yield takeEvery(auphonic.startProduction, handleStartProduction, auphonicApi)
  yield takeEvery(auphonic.deselectProduction, handleDeselectProduction, auphonicApi)

  // poll production updates while production is running
  // TODO: start polling when loading a production that is in production
  yield takeEvery(auphonic.startProduction, function* () {
    yield put(auphonic.startPolling())
  })
  yield call(pollWatcherSaga, auphonicApi)
}

function* pollWatcherSaga(auphonicApi: AuphonicApiClient) {
  while (true) {
    yield take(auphonic.START_POLLING)
    yield race([call(pollProductionSaga, auphonicApi), take(auphonic.STOP_POLLING)])
  }
}

function* pollProductionSaga(auphonicApi: AuphonicApiClient) {
  while (true) {
    let uuid: string = yield select(selectors.auphonic.productionId)

    let {
      result: { data: production },
    } = yield auphonicApi.get(`production/${uuid}.json`)

    yield put(auphonic.setProduction(production))

    // DONE
    if (production.status == 3) {
      yield put(auphonic.stopPolling())
    }

    yield delay(2500)
  }
}

function* handleDeselectProduction(auphonicApi: AuphonicApiClient) {
  const {
    result: { data: productions },
  } = yield auphonicApi.get(`productions.json`, { limit: 10, minimal_data: true })
  yield put(auphonic.setProductions(productions))
}

function* handleStartProduction(
  auphonicApi: AuphonicApiClient,
  action: { type: string; payload: any }
) {
  const uuid = action.payload.uuid
  const { authkey }: WebhookConfig = yield select(selectors.episode.auphonicWebhookConfig)
  const isWebhookEnabled: boolean = yield select(selectors.auphonic.publishWhenDone)
  const baseUrl: String = yield select(selectors.runtime.baseUrl)
  const postId: Number = yield select(selectors.post.id)

  const webhookUrl = baseUrl + '/?podlove-auphonic-production=' + postId + '&authkey=' + authkey
  const productionPayload = {
    webhook: isWebhookEnabled ? webhookUrl : '',
  }

  // update webhook config
  const {
    result: { data: _production },
  } = yield auphonicApi.post(`production/${uuid}.json`, productionPayload)

  // TODO: for productions with webhook enabled, should I explicitly re-fetch
  // the episode when production is done (or poll for a bit)? Otherwise backend
  // and frontend might be out of sync because the webhook overrides some
  // episode data.

  // start production
  const {
    result: { data: production },
  } = yield auphonicApi.post(`production/${uuid}/start.json`, {})

  yield put(auphonic.setProduction(production))
}

function* handleSaveTrack(auphonicApi: AuphonicApiClient, uuid: String, trackWrapper: any) {
  let payload = trackWrapper.payload

  const id_old = payload.id
  const id_new = payload.id_new

  const needs_upload = !!trackWrapper.upload?.file

  delete payload.id_new
  payload.id = id_new

  switch (trackWrapper.state) {
    case 'edited':
      yield auphonicApi.post(`production/${uuid}/multi_input_files/${id_old}.json`, payload)
      if (needs_upload) {
        yield auphonicApi.upload(`production/${uuid}/upload.json`, trackWrapper.upload)
      }
      break
    case 'new':
      yield auphonicApi.post(`production/${uuid}.json`, {
        multi_input_files: [trackWrapper.payload],
      })
      if (needs_upload) {
        yield auphonicApi.upload(`production/${uuid}/upload.json`, trackWrapper.upload)
      }
      break
  }
}

function* handleSaveProduction(
  auphonicApi: AuphonicApiClient,
  action: { type: string; payload: any }
) {
  yield put(auphonic.startSaving())

  const uuid = action.payload.uuid
  const productionPayload = action.payload.productionPayload
  const tracksPayload = action.payload.tracksPayload

  // delete all existing chapters, otherwise we append them
  yield auphonicApi.delete(`production/${uuid}/chapters.json`)

  // save multi_input_files by saving/updating each track individually
  yield all(
    tracksPayload.map((trackWrapper: any) => call(handleSaveTrack, auphonicApi, uuid, trackWrapper))
  )

  // handle single track if input_file is set
  // FIXME: only upload when changed, see multitrack logic
  const input_file = productionPayload.input_file
  if (typeof input_file == 'object') {
    yield auphonicApi.upload(`production/${uuid}/upload.json`, {
      file: input_file,
    })
    delete productionPayload.input_file
  }

  // after the tracks, update all other metadata
  const {
    result: { data: production },
  } = yield auphonicApi.post(`production/${uuid}.json`, productionPayload)

  yield put(auphonic.setProduction(production))
  yield put(auphonic.stopSaving())
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
  const selection: any = yield select(selectors.auphonic.fileSelections)

  // set default, but only if necessary
  if (!selection[currentKey].fileSelection) {
    // select first available file
    yield put(
      auphonic.updateFileSelection({
        key: currentKey,
        prop: 'fileSelection',
        value: action.payload.files[0],
      })
    )
  }
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

type WebhookConfig = {
  authkey: String
  enabled: boolean
}

function* updateWebhookConfig(api: PodloveApiClient) {
  const config: WebhookConfig = yield select(selectors.episode.auphonicWebhookConfig)
  const enabled: boolean = yield select(selectors.auphonic.publishWhenDone)

  // skip if nothing changed
  if (config.enabled == enabled) {
    return
  }

  yield put(
    episode.update({ prop: 'auphonic_webhook_config', value: { ...config, enabled: enabled } })
  )
}

function* initializeWebhookConfig(api: PodloveApiClient) {
  const config: WebhookConfig = yield select(selectors.episode.auphonicWebhookConfig)
  const enabled: boolean = yield select(selectors.auphonic.publishWhenDone)

  // skip if it already exists
  if (config && config.authkey) {
    return
  }

  const authkey = uuidv4()

  yield put(
    episode.update({
      prop: 'auphonic_webhook_config',
      value: {
        authkey,
        enabled: enabled || false,
      },
    })
  )
}

function* memorizeSelectedProduction(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)
  const uuid: string = yield select(selectors.auphonic.productionId)

  yield api.put(`episodes/${episodeId}`, { auphonic_production_id: uuid })
}

function* forgetSelectedProduction(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)

  yield api.put(`episodes/${episodeId}`, { auphonic_production_id: '' })
}

function* maybeRestoreProductionSelection() {
  const episodeId: string = yield select(selectors.episode.id)
  const memorizedProductionId: string = yield select(selectors.episode.auphonicProductionId)
  const selectedProductionId: string = yield select(selectors.auphonic.productionId)
  const productions: auphonic.Production[] = yield select(selectors.auphonic.productions)

  if (!selectedProductionId && memorizedProductionId && episodeId) {
    const production = productions.find((production) => production.uuid == memorizedProductionId)

    if (production) {
      yield put(auphonic.setProduction(production))
    }
  }
}

export default function () {
  return function* () {
    yield takeFirst(auphonic.INIT, auphonicSaga)
  }
}
