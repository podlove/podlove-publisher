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
import { State } from '../store'
import { get } from 'lodash'
import Timestamp from '@lib/timestamp'

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

    yield takeEvery(auphonic.SET_PRESET, memorizeSelectedPreset)
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
  yield call(maybeRestorePresetSelection)
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
      yield put(episode.update({ prop: 'slug', value: production.output_basename }))
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

  yield call(handleSaveProduction, auphonicApi, {
    type: auphonic.SAVE_PRODUCTION,
    payload: { uuid: uuid },
  })

  const webhookConfig: WebhookConfig | null = yield select(selectors.episode.auphonicWebhookConfig)
  const isWebhookEnabled: boolean = yield select(selectors.auphonic.publishWhenDone)
  const baseUrl: String = yield select(selectors.runtime.baseUrl)
  const postId: Number = yield select(selectors.post.id)

  const webhookUrl =
    baseUrl + '/?podlove-auphonic-production=' + postId + '&authkey=' + webhookConfig?.authkey
  const productionPayload = {
    webhook: webhookConfig && isWebhookEnabled ? webhookUrl : '',
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
  const response: { result: any; error: any } = yield auphonicApi.post(
    `production/${uuid}/start.json`,
    {}
  )

  if (response.result) {
    yield put(auphonic.setProduction(response.result.data))
  } else {
    console.warn(response.error.error_message)
  }
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

type PreparedFileSelection = {
  service?: string | null
  value?: string | null
}

const prepareFile = (selection: auphonic.FileSelection): PreparedFileSelection => {
  if (!selection) {
    return {}
  }

  switch (selection.currentServiceSelection) {
    case 'url':
      return { service: 'url', value: selection.urlValue }
    case 'file':
      return { service: 'file', value: selection.fileValue }
    default:
      return { service: selection.currentServiceSelection, value: selection.fileSelection }
  }
}

function getFileSelectionsForSingleTrack(state: State): PreparedFileSelection {
  const selections = get(state, ['auphonic', 'file_selections'])
  const production_uuid = get(state, ['auphonic', 'production', 'uuid'], '')

  return prepareFile(get(selections, production_uuid))
}

function getFileSelectionsForMultiTrack(state: State): PreparedFileSelection[] {
  const selections = get(state, ['auphonic', 'file_selections'])
  const production_uuid = get(state, ['auphonic', 'production', 'uuid'], '')
  const tracks = get(state, ['auphonic', 'tracks'], [])

  //@ts-ignore
  return tracks.reduce((agg, _track, index) => {
    agg.push(prepareFile(get(selections, `${production_uuid}_t${index}`)))
    return agg
  }, [])
}

function getTracksPayload(state: State): any {
  const isMultitrack = get(state, ['auphonic', 'production', 'is_multitrack'], false)
  const tracks = get(state, ['auphonic', 'tracks'], [])

  if (!isMultitrack) {
    return []
  }

  const fileSelections = getFileSelectionsForMultiTrack(state)

  return tracks
    .map((track, index) => {
      const state = track.save_state

      if (state == 'unchanged') {
        return {}
      }

      let upload = {}

      // FIXME: currently service is always url when selecting an existing production
      let fileReference = {}
      if (fileSelections[index].service == 'url') {
        fileReference = {
          input_file: fileSelections[index].value,
        }
      } else if (fileSelections[index].service == 'file') {
        upload = {
          track_id: track.identifier_new,
          file: fileSelections[index].value,
        }
      } else {
        fileReference = {
          service: fileSelections[index].service,
          input_file: fileSelections[index].value,
        }
      }

      return {
        state,
        upload,
        payload: {
          type: 'multitrack',
          id: track.identifier,
          id_new: track.identifier_new,
          ...fileReference,
          algorithms: {
            denoise: track.noise_and_hum_reduction,
            filtering: track.filtering,
            backforeground: track.fore_background,
          },
        },
      }
    })
    .filter((t) => Object.keys(t).length > 0)
}

function getProductionPayload(state: State): object {
  let payload = get(state, ['auphonic', 'productionPayload'], {})

  // remove output_files from payload, because it doubles them
  const { output_files, ...newPayload } = payload
  const episode_poster = state.episode.poster || state.podcast.poster
  const maybe_output_basename = state.episode.slug ? { output_basename: state.episode.slug } : {}

  return {
    ...newPayload,
    ...maybe_output_basename,
    // NOTE: image is not actually sent; it's sent as a separate upload and
    // removed from the payload before saving metadata. reason: Auphonic may not
    // have access to the URL here (for example in local development), so
    // sending the file as upload is more reliable.
    image: episode_poster,
    metadata: {
      ...newPayload.metadata,
      title: state.episode.title,
      subtitle: state.episode.subtitle,
      summary: state.episode.summary,
      artist: state.podcast.author_name,
      album: state.podcast.title,
      url: state.podcast.link,
      track: state.episode.number,
    },
    chapters: state.chapters.chapters.map((chapter) => {
      return {
        title: chapter.title,
        url: chapter.href,
        start: new Timestamp(chapter.start).pretty,
      }
    }),
  }
}

function getSaveProductionPayload(state: State): object {
  const isMultitrack = get(state, ['auphonic', 'production', 'is_multitrack'], false)
  const productionPayload = getProductionPayload(state)

  let fileReference = {}
  // for single track, add file selection to payload
  if (!isMultitrack) {
    const fileSelections = getFileSelectionsForSingleTrack(state)
    if (fileSelections.service == 'url') {
      fileReference = {
        input_file: fileSelections.value,
      }
    } else if (fileSelections.service == 'file') {
      fileReference = {
        input_file: fileSelections.value,
      }
    } else {
      fileReference = {
        service: fileSelections.service,
        input_file: fileSelections.value,
      }
    }
  }

  return {
    ...productionPayload,
    ...fileReference,
  }
}

function* handleSaveProduction(
  auphonicApi: AuphonicApiClient,
  action: { type: string; payload: any }
) {
  yield put(auphonic.startSaving())

  const uuid = action.payload.uuid
  //@ts-ignore
  const productionPayload = yield select(getSaveProductionPayload)
  //@ts-ignore
  const tracksPayload = yield select(getTracksPayload)

  // delete all existing chapters, otherwise we append them
  //@ts-ignore
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

  // upload cover image
  const poster_file = productionPayload.image

  fetch(poster_file)
    .then((res) => res.blob())
    .then((blob) => {
      const ext = blob.type.includes('png') ? 'png' : 'jpg'
      const filename = 'image.' + ext
      const image_file = new File([blob], filename, { type: blob.type })

      let r = auphonicApi.upload(`production/${uuid}/upload.json`, {
        image: image_file,
      })
    })

  delete productionPayload.image

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
  return `New Production`
}

function* handleCreateProduction(auphonicApi: AuphonicApiClient) {
  const presetUUID: string = yield select(selectors.auphonic.preset)
  const { result } = yield auphonicApi.post(`productions.json`, {
    preset: presetUUID,
    metadata: { title: defaultTitle() },
  })
  const production = result.data

  yield put(auphonic.setProduction(production))
}

function* handleCreateMultitrackProduction(auphonicApi: AuphonicApiClient) {
  const presetUUID: string = yield select(selectors.auphonic.preset)
  const { result } = yield auphonicApi.post(`productions.json`, {
    preset: presetUUID,
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
  //@ts-ignore
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

export type WebhookConfig = {
  authkey: String
  enabled: boolean
}

function* updateWebhookConfig(api: PodloveApiClient) {
  const config: WebhookConfig | null = yield select(selectors.episode.auphonicWebhookConfig)
  const enabled: boolean = yield select(selectors.auphonic.publishWhenDone)

  // skip if nothing changed
  if (!config || config.enabled == enabled) {
    return
  }

  yield put(
    episode.update({ prop: 'auphonic_webhook_config', value: { ...config, enabled: enabled } })
  )
}

function* initializeWebhookConfig(api: PodloveApiClient) {
  const config: WebhookConfig | null = yield select(selectors.episode.auphonicWebhookConfig)
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

function* memorizeSelectedPreset() {
  const preset: string = yield select(selectors.auphonic.preset)

  if (localStorage) {
    localStorage.setItem('podlove-auphonic-preset', preset)
  }
}

function* maybeRestorePresetSelection() {
  let savedPreset: string | null = null

  if (localStorage) {
    savedPreset = localStorage.getItem('podlove-auphonic-preset')
    if (savedPreset) {
      yield put(auphonic.setPreset(savedPreset))
    }
  }
}

export default function () {
  return function* () {
    yield takeFirst(auphonic.INIT, auphonicSaga)
  }
}
