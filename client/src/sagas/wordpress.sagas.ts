import { call, put, select, takeEvery } from 'redux-saga/effects'

import * as lifecycleStore from '@store/lifecycle.store'
import * as wordpressStore from '@store/wordpress.store'
import * as episodeStore from '@store/episode.store'
import selectors from '@store/selectors'

import { takeFirst, channel } from './helper'

import * as wordpress from '../lib/wordpress'
import { get } from 'lodash'
import { Action } from 'redux'

function* wordpressSaga(): any {
  const generateTitle: boolean = yield select(selectors.settings.autoGenerateEpisodeTitle)

  if (typeof wordpress.store?.subscribe !== 'undefined') {
    yield takeEvery(yield call(channel, wordpress.store?.subscribe), wordpressGutenbergUpdate)
  }

  if (wordpress.postTitleInput) {
    yield takeEvery(yield call(channel, wordpress.postTitleListener), postTitleUpdate)
  }

  if (generateTitle) {
    yield takeEvery(episodeStore.SET, updatePostTitle)
    yield takeEvery(episodeStore.UPDATE, updatePostTitle)
  }

  if (wordpress.media) {
    yield takeEvery(wordpressStore.SELECT_MEDIA_FROM_LIBRARY as any, selectMediaFromLibrary)
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
      value: get(event, ['target', 'value']),
    })
  )
}

function* updatePostTitle() {
  if (!wordpress.postTitleInput) {
    return
  }

  const template: string = yield select(selectors.settings.blogTitleTemplate)

  if (!template) {
    return
  }

  const title: string = yield select(selectors.episode.title)
  const episodeNumber: string = yield select(selectors.episode.number)
  const mnemonic: string = yield select(selectors.podcast.mnemonic)
  // TODO: get from episode?
  const seasonNumber: string = ''
  const padding: number = yield select(selectors.settings.episodeNumberPadding)

  wordpress.postTitleInput.value = template
    .replace('%mnemonic%', mnemonic || '')
    .replace('%episode_number%', (episodeNumber || '').padStart(padding || 0, '0'))
    .replace('%season_number%', seasonNumber || '')
    .replace('%episode_title%', title || '')
}

function* selectMediaFromLibrary(action: { payload: { onSuccess: Action } }) {
  const successAction = get(action, ['payload', 'onSuccess'])

  if (!successAction) {
    console.warn('Missing successAction')
    return
  }

  const mediaLibrary = wordpress.media({
    title: 'Select or Upload Media Of Your Chosen Persuasion',
    button: {
      text: 'Use this media',
    },
    multiple: false, // Set to true to allow multiple files to be selected
  })

  const mediaSelectionDialogue: Promise<string> = new Promise((resolve) => {
    mediaLibrary.on('select', () => {
      const { url } = mediaLibrary.state().get('selection').first().toJSON()
      resolve(url)
    })
  })

  mediaLibrary.open()

  try {
    const url: string = yield mediaSelectionDialogue
    yield put({
      ...successAction,
      payload: url,
    })
  } finally {
  }
}

export default function () {
  return function* () {
    yield takeFirst(lifecycleStore.INIT, wordpressSaga)
  }
}
