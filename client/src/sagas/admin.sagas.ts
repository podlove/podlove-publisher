import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { put, select, takeEvery } from 'redux-saga/effects'
import * as admin from '../store/admin.store'
import * as auphonic from '../store/auphonic.store'
import * as episode from '../store/episode.store'
import { createApi } from './api'
import { takeFirst } from './helper'
import { v4 as uuidv4 } from 'uuid'

type WebhookConfig = {
  authkey: String
  enabled: boolean
}

function* adminSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()

  yield takeEvery(episode.SET, loadOrInitializeWebhookConfigFromPodlove, apiClient)
  yield takeEvery(auphonic.UPDATE_WEBHOOK, persistWebhookToggleChange, apiClient)
}

function* loadOrInitializeWebhookConfigFromPodlove(api: PodloveApiClient) {
  const episodeId: number = yield select(selectors.episode.id)
  const { result } = yield api.get(`admin/auphonic/webhook_config/${episodeId}`)

  if (result) {
    // load existing config
    yield put(admin.update({ prop: 'auphonicWebhookConfig', value: result }))
  } else {
    // initialize config, then load

    const config = {
      authkey: uuidv4(),
      enabled: false,
    }

    api.put(`admin/auphonic/webhook_config/${episodeId}`, config)
    yield put(admin.update({ prop: 'auphonicWebhookConfig', value: config }))
  }
}

function* persistWebhookToggleChange(api: PodloveApiClient) {
  const episodeId: number = yield select(selectors.episode.id)
  const config: WebhookConfig = yield select(selectors.admin.auphonicWebhookConfig)
  const enabled: boolean = yield select(selectors.auphonic.publishWhenDone)

  const newConfig = { ...config, enabled: enabled }

  yield put(admin.update({ prop: 'auphonicWebhookConfig', value: newConfig }))
  api.put(`admin/auphonic/webhook_config/${episodeId}`, newConfig)
}

export default function () {
  return function* () {
    yield takeFirst(episode.INIT, adminSaga)
  }
}
