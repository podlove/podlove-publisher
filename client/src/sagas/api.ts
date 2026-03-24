import { call, select } from 'redux-saga/effects'
import { podlove } from '../lib/api'
import { selectors, store } from '@store'
import { notify } from '@store/notification.store'
import { waitFor } from './helper'

export function* createApi() {
  yield call(waitFor, selectors.lifecycle.bootstrapped)
  const base: string = yield select(selectors.runtime.base)
  const nonce: string = yield select(selectors.runtime.nonce)
  const auth: string = yield select(selectors.runtime.auth)
  const bearer: string = yield select(selectors.runtime.bearer)

  const errorHandler = function (errorData: any) {
    let message = 'An error occurred'

    if (typeof errorData === 'string') {
      message = errorData
    } else if (errorData && typeof errorData === 'object') {
      if (errorData.code && errorData.message) {
        message = `${errorData.code}: ${errorData.message}`
      } else {
        message = errorData.message || errorData.code || 'An error occurred'
      }
    }

    store.dispatch(notify({ type: 'error', message }))
  }

  return podlove({ base, version: 'v2', nonce, auth, bearer, errorHandler })
}
