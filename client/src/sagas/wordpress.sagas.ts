import { call, put, select, takeEvery } from 'redux-saga/effects'

import * as lifecycleStore from '@store/lifecycle.store'
import * as wordpressStore from '@store/wordpress.store'
import * as episodeStore from '@store/episode.store'
import selectors from '@store/selectors'

import { takeFirst, channel } from './helper'

import * as wordpress from '../lib/wordpress'
import { get } from 'lodash'

function* wordpressSaga(): any {
  const titleInput = document.querySelector('input[name="post_title"]')
  const generateTitle: boolean = yield select(selectors.settings.autoGenerateEpisodeTitle);


  if (typeof wordpress.store?.subscribe !== 'undefined') {
    yield takeEvery(yield call(channel, wordpress.store?.subscribe), wordpressGutenbergUpdate)
  }

  if (titleInput) {
    const titleUpdate = (cb: EventListener) => titleInput.addEventListener('input', cb)
    yield takeEvery(yield call(channel, titleUpdate), postTitleUpdate)
  }

  if (generateTitle) {
    yield takeEvery(episodeStore.UPDATE, updatePostTitle)
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

function* postTitleUpdate(event: InputEvent) {
  yield put(
    wordpressStore.update({
      prop: 'title',
      value: get(event, ['target', 'value'])
    })
  )
}

function* updatePostTitle() {
  const title: string = yield select(selectors.episode.title)

  console.log(title)

}

export default function () {
  return function* () {
    yield takeFirst(lifecycleStore.INIT, wordpressSaga)
  }
}
