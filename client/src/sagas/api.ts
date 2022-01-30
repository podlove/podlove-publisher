import { select } from 'redux-saga/effects'
import { podlove } from '../lib/api'
import { selectors, store } from '@store'
import { notify } from '@store/notification.store'

export function* createApi() {
  const base: string = yield select(selectors.runtime.base)
  const nonce: string = yield select(selectors.runtime.nonce)
  const auth: string = yield select(selectors.runtime.auth)
  const bearer: string = yield select(selectors.runtime.bearer)

  const errorHandler = function (message: string) {
    store.dispatch(notify({ type: 'error', message }))
  }

  return podlove({ base, version: 'v2', nonce, auth, bearer, errorHandler })
}
