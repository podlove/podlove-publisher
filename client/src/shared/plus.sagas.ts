import { fork, put, call, takeEvery } from 'redux-saga/effects'
import * as plus from '@store/plus.store'
import { takeFirst } from '../sagas/helper'
type CreateApi = () => any

export const createPlusRootSaga = (createApi: CreateApi) => {
  function* plusSaga(): Generator<any, void, any> {
    const apiClient = yield createApi()
    yield fork(initialize, apiClient)
  }

  function* initialize(api: any): Generator<any, void, any> {
    const { result } = yield api.get(`admin/plus/features`)

    yield put(plus.setFeature({ feature: 'fileStorage', value: result.file_storage }))
    yield put(plus.setFeature({ feature: 'feedProxy', value: result.feed_proxy }))

    yield takeEvery(plus.SET_FEATURE, setFeature, api)
    yield takeEvery(plus.GET_TOKEN, getToken, api)
    yield takeEvery(plus.SAVE_TOKEN, saveToken, api)

    yield put(plus.getToken())
  }

  function* setFeature(api: any, action: ReturnType<typeof plus.setFeature>): Generator<any, void, any> {
    const { feature, value } = action.payload
    yield api.post(`admin/plus/set_feature`, { feature, value })
  }

  function* getToken(api: any): Generator<any, void, any> {
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

  function* validateToken(api: any, token: string): Generator<any, void, any> {
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

  function* saveToken(api: any, action: ReturnType<typeof plus.saveToken>): Generator<any, void, any> {
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

  return function () {
    return function* () {
      yield takeFirst(plus.INIT, plusSaga)
    }
  }
}
