import { select } from 'redux-saga/effects'
import { podlove } from '../lib/api'
import { selectors } from '@store'

export function* createApi() {
  const base = yield select(selectors.runtime.base);
  const nonce = yield select(selectors.runtime.nonce);
  const auth = yield select(selectors.runtime.auth);
  const bearer = yield select(selectors.runtime.bearer);

  return podlove({ base, version: 'v2', nonce, auth, bearer });
}
