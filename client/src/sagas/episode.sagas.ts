import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { get, isEmpty } from 'lodash'
import { Action } from 'redux'
import { debounce, fork, put, select, takeEvery } from 'redux-saga/effects'
import { PodloveEpisode } from '../types/episode.types'
import * as auphonic from '../store/auphonic.store'
import * as episode from '../store/episode.store'
import * as mediafiles from '../store/mediafiles.store'
import * as wordpress from '../store/wordpress.store'
import { createApi } from './api'
import { WebhookConfig } from './auphonic.sagas'
import { takeFirst } from './helper'

let EPISODE_UPDATE: { [key: string]: any } = {}

function* episodeSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)

  yield takeEvery(episode.UPDATE, collectEpisodeUpdate)
  yield debounce(1000, episode.UPDATE, save, apiClient)
  yield debounce(50, episode.QUICKSAVE, save, apiClient)
  yield takeEvery(episode.SAVED, maybeMarkSlugAsChanged)
  yield takeEvery(episode.SELECT_POSTER, selectImageFromLibrary)
  yield takeEvery(episode.SET_POSTER, updatePoster)
  yield takeEvery(wordpress.UPDATE, updatePosterFromGutenberg)
  yield takeEvery(episode.SET, updateAuphonicWebhookConfig)
}

function* updateAuphonicWebhookConfig() {
  const config: WebhookConfig | null = yield select(selectors.episode.auphonicWebhookConfig)
  if (config) {
    yield put(auphonic.updateWebhook(config.enabled))
  }
}

function* initialize(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)
  if (!episodeId) {
    return
  }

  const { result: episodesResult }: { result: PodloveEpisode } = yield api.get(
    `episodes/${episodeId}`
  )

  if (episodesResult) {
    if (episodesResult.slug === null) {
      yield put(mediafiles.enableSlugAutogen())
    }

    yield put(episode.set(episodesResult))
  }
}

function* collectEpisodeUpdate(action: Action) {
  const prop = get(action, ['payload', 'prop'])
  const value = get(action, ['payload', 'value'], null)

  if (!prop) {
    return
  }

  // If trying to update slug when frozen, block the update
  if (prop === 'slug') {
    const slugFrozen: boolean = yield select(selectors.episode.slugFrozen)
    if (slugFrozen) {
      console.warn('Attempted to update frozen slug - update blocked')
      return
    }
  }

  EPISODE_UPDATE[prop] = value
}

function* save(api: PodloveApiClient, action: Action) {
  const episodeId: string = yield select(selectors.episode.id)

  if (isEmpty(EPISODE_UPDATE)) {
    return
  }

  yield api.put(`episodes/${episodeId}`, EPISODE_UPDATE, { query: { skip_validation: '1' } })
  yield put(episode.saved(EPISODE_UPDATE))

  EPISODE_UPDATE = {}
}

function* maybeMarkSlugAsChanged(action: { type: string; payload: object }) {
  if (Object.keys(action.payload).includes('slug')) {
    yield put(episode.slugChanged())
  }
}

function* selectImageFromLibrary() {
  yield put(wordpress.selectMediaFromLibrary({ onSuccess: { type: episode.SET_POSTER } }))
}

function* updatePoster(action: Action) {
  yield put(episode.update({ prop: 'episode_poster', value: get(action, ['payload']) }))
}

function* updatePosterFromGutenberg(action: { type: string; payload: object }) {
  const poster_setting: string = yield select(selectors.settings.imageAsset)

  // only apply if the featured media is used for the episode cover
  if (poster_setting != 'post-thumbnail') {
    return
  }

  // only apply if the current event is about featured_media
  if (get(action, ['payload', 'prop']) != 'featured_media') {
    return
  }

  const img_url = get(action, ['payload', 'value', 'source_url'])
  yield put(episode.update({ prop: 'poster', value: img_url }))
}

export default function () {
  return function* () {
    yield takeFirst(episode.INIT, episodeSaga)
  }
}
