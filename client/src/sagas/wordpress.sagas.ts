import * as lifecycleStore from '@store/lifecycle.store'
import * as wordpressStore from '@store/wordpress.store'
import { call, put, takeEvery } from 'redux-saga/effects'

import { takeFirst, channel } from './helper'

import * as wordpress from '../lib/wordpress'

function* wordpressSaga(): any {
  if (typeof wordpress.store?.subscribe !== 'undefined') {
    yield takeEvery(yield call(channel, wordpress.store?.subscribe), wordpressGutenbergUpdate)
  }
}

function* wordpressGutenbergUpdate() {
  yield put(
    wordpressStore.update({
      prop: 'title',
      value: wordpress.store.select('core/editor').getEditedPostAttribute('title'),
    })
  )
}

export default function () {
  return function* () {
    yield takeFirst(lifecycleStore.INIT, wordpressSaga)
  }
}
