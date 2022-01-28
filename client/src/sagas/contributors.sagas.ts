import { put } from 'redux-saga/effects'

import * as contributors from '@store/contributors.store'
import { PodloveApiClient } from '@lib/api'

import { takeFirst } from './helper'
import { createApi } from './api'

function* contributorsSaga() {
  const apiClient: PodloveApiClient = yield createApi();
  const { result } = yield apiClient.get('contributors')

  if (result) {
    yield put(contributors.set(result))
  }
}

export default function () {
  return function* () {
    yield takeFirst(contributors.INIT, contributorsSaga)
  }
}

