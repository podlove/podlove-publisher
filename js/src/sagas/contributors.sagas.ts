import { put } from 'redux-saga/effects'

import * as lifecycle from '@store/lifecycle.store'
import * as contributors from '@store/contributors.store'

import { takeFirst } from './helper'
import { createApi } from './api'

function contributorsSaga() {
  return function* () {
    yield takeFirst(lifecycle.INIT, initialize)
  }
}

function* initialize() {
  const apiClient = yield createApi();

  const { result, error } = yield apiClient.get('contributors')

  if (result) {
    yield put(contributors.set(result))
  }
}

export default contributorsSaga
