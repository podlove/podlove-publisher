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
  yield takeEvery(plus.GET_TOKEN, getToken, api)
  yield takeEvery(plus.SAVE_TOKEN, saveToken, api)

  yield put(plus.getToken())
}

function* setFeature(api: PodloveApiClient, action: ReturnType<typeof plus.setFeature>) {
  const { feature, value } = action.payload
  yield api.post(`admin/plus/set_feature`, { feature, value })
}

function* getToken(api: PodloveApiClient) {
  try {
    yield put(plus.setLoading(true))
    const { result } = yield api.get(`admin/plus/token`)
    yield put(plus.setToken(result.token || ''))

    if (result.token) {
      yield call(validateToken, api, result.token)
    }
  } catch (error) {
    console.error('Failed to get token:', error)
    yield put(plus.setToken(''))
    yield put(plus.setUser(null))
  } finally {
    yield put(plus.setLoading(false))
  }
}

function* validateToken(api: PodloveApiClient, token: string) {
  try {
    const { result } = yield api.get(`admin/plus/validate_token`)
    if (result.user) {
      yield put(plus.setUser(result.user))
    } else {
      yield put(plus.setUser(null))
    }
  } catch (error) {
    console.error('Failed to validate token:', error)
    yield put(plus.setUser(null))
  }
}

function* saveToken(api: PodloveApiClient, action: ReturnType<typeof plus.saveToken>) {
  try {
    yield put(plus.setSaving(true))
    const token = action.payload
    yield api.post(`admin/plus/save_token`, { token })

    if (token) {
      yield call(validateToken, api, token)
    } else {
      yield put(plus.setUser(null))
    }
  } catch (error) {
    console.error('Failed to save token:', error)
  } finally {
    yield put(plus.setSaving(false))
  }
}

export default function () {
  return function* () {
    yield takeFirst(plus.INIT, plusSaga)
  }
}
