import { select } from 'redux-saga/effects'
import { podlove, PodloveApiClient } from '../lib/api'
import { selectors } from '@store'

export function* createApi() {
  const base: string = yield select(selectors.runtime.base)
  const nonce: string = yield select(selectors.runtime.nonce)
  const auth: string = yield select(selectors.runtime.auth)
  const bearer: string = yield select(selectors.runtime.bearer)

  return podlove({ base, version: 'v2', nonce, auth, bearer })
}
