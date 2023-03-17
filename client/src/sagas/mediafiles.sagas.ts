import * as mediafiles from '@store/mediafiles.store'
import { put } from 'redux-saga/effects'
import { takeFirst } from './helper'

function* mediafilesSaga(): any {
  console.log('hey')
  yield put(mediafiles.initDone())
}

export default function () {
  return function* () {
    yield takeFirst(mediafiles.INIT, mediafilesSaga)
  }
}
