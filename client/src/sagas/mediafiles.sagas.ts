import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { all, call, delay, fork, put, select, takeEvery, throttle } from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import * as episode from '@store/episode.store'
import * as wordpress from '@store/wordpress.store'
import { MediaFile } from '@store/mediafiles.store'
import { takeFirst } from './helper'
import { createApi } from './api'
import { Action } from 'redux'
import { get } from 'lodash'

function* mediafilesSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)
  const {
    result: { results: files },
  }: { result: { results: MediaFile[] } } = yield api.get(`episodes/${episodeId}/media`)

  if (files) {
    yield put(mediafiles.set(files))
  }

  yield takeEvery(mediafiles.ENABLE, handleEnable, api)
  yield takeEvery(mediafiles.DISABLE, handleDisable, api)
  yield takeEvery(mediafiles.VERIFY, handleVerify, api)
  yield takeEvery(episode.SAVED, maybeReverify, api)
  yield takeEvery(episode.SAVED, maybeUpdateSlug, api)
  yield throttle(
    2000,
    [mediafiles.ENABLE, mediafiles.DISABLE, mediafiles.UPDATE],
    maybeUpdateDuration,
    api
  )
  yield takeEvery(mediafiles.UPLOAD_INTENT, selectMediaFromLibrary)
  yield takeEvery(mediafiles.SET_UPLOAD_URL, setUploadMedia)

  yield put(mediafiles.initDone())
}

function* selectMediaFromLibrary() {
  yield put(wordpress.selectMediaFromLibrary({ onSuccess: { type: mediafiles.SET_UPLOAD_URL } }))
}

function* setUploadMedia(action: Action) {
  const url = get(action, ['payload'])
  const slug = url.split('\\').pop().split('/').pop().split('.').shift()

  yield put(episode.update({ prop: 'slug', value: slug }))
  yield put(episode.quicksave())
}

function* maybeUpdateDuration(api: PodloveApiClient) {
  const files: MediaFile[] = yield select(selectors.mediafiles.files)
  const enabledFiles = files.filter((file) => file.enable && file.size && file.url)
  const audioFiles = enabledFiles.filter((file) => file.url.match(/\.(mp3|mp4|m4a|ogg|oga|opus)$/))

  if (audioFiles.length === 0) {
    yield put(episode.update({ prop: 'duration', value: '0' }))
  } else {
    const url = audioFiles[0].url
    const result: number = yield fetchDuration(url)
    yield put(episode.update({ prop: 'duration', value: result.toString() }))
  }
}

function* handleEnable(api: PodloveApiClient, action: { type: string; payload: number }) {
  const episodeId: string = yield select(selectors.episode.id)
  const asset_id = action.payload

  const { result } = yield api.put(`episodes/${episodeId}/media/${asset_id}/enable`, {})

  const fileUpdate: Partial<MediaFile> = {
    asset_id: asset_id,
    url: result.file_url,
    size: result.file_size,
  }

  yield put(mediafiles.update(fileUpdate))
}

function* handleDisable(api: PodloveApiClient, action: { type: string; payload: number }) {
  const episodeId: string = yield select(selectors.episode.id)
  const asset_id = action.payload

  yield api.put(`episodes/${episodeId}/media/${asset_id}/disable`, {})
}

function* maybeReverify(api: PodloveApiClient, action: { type: string; payload: object }) {
  const episodeId: number = yield select(selectors.episode.id)
  const mediaFiles: MediaFile[] = yield select(selectors.mediafiles.files)

  if (!Object.keys(action.payload).includes('slug')) {
    return
  }

  // verify all
  yield all(mediaFiles.map((file) => call(verifyEpisodeAsset, api, episodeId, file.asset_id)))
}

// THOUGHTS
// - auto gen logic is both here and in the wordpress saga. needs to at least be
//   dry
// - previous slug generation was on PHP side, using WordPress' sanitize_title,
//   which I do not want to reimplement in PHP
// - maybe have a dedicated endpoint to push an unsanitized slug? But then I
//   might as well send a "just generate me a slug" ping instead and do
//   everything on the backend. Sounds best actually.
function* maybeUpdateSlug(api: PodloveApiClient, action: { type: string; payload: object }) {
  const changedKeys = Object.keys(action.payload)
  const interestingKeys = ['title', 'number']
  const slugNeedsUpdating = changedKeys.some((key) => interestingKeys.includes(key))

  const generateTitle: boolean = yield select(selectors.settings.autoGenerateEpisodeTitle)
  const template: string = yield select(selectors.settings.blogTitleTemplate)
  const title: string = yield select(selectors.episode.title)
  const episodeNumber: string = yield select(selectors.episode.number)
  const mnemonic: string = yield select(selectors.podcast.mnemonic)
  // TODO: get from episode?
  const seasonNumber: string = ''
  const padding: number = yield select(selectors.settings.episodeNumberPadding)

  // So ugly: I don't want to have to know about the title template here, or if it's even used
  if (slugNeedsUpdating) {
    let newSlug = ''

    if (generateTitle && template) {
      const generatedTitle = template
        .replace('%mnemonic%', mnemonic || '')
        .replace('%episode_number%', (episodeNumber || '').padStart(padding || 0, '0'))
        .replace('%season_number%', seasonNumber || '')
        .replace('%episode_title%', title || '')
      newSlug = generatedTitle
    } else {
      newSlug = title
    }

    // FIXME: `newSlug` is currently just the title. Needs to be slugged. But
    // maybe I don't do all that here anyway, see thoughts above.

    yield put(episode.update({ prop: 'slug', value: newSlug }))
  }
}

function* verifyEpisodeAsset(api: PodloveApiClient, episodeId: number, assetId: number) {
  const mediaFiles: MediaFile[] = yield select(selectors.mediafiles.files)
  const prevMediaFile: MediaFile | undefined = mediaFiles.find((mf) => mf.asset_id == assetId)

  yield put(
    mediafiles.update({
      asset_id: assetId,
      is_verifying: true,
    })
  )

  const { result } = yield api.put(`episodes/${episodeId}/media/${assetId}/verify`, {})

  // auto-enable if file size changed from zero to non-zero
  const enable = (!prevMediaFile?.size && result.file_size) || prevMediaFile?.enable

  const fileUpdate: Partial<MediaFile> = {
    asset_id: assetId,
    url: result.file_url,
    size: result.file_size,
    enable: enable,
    is_verifying: false,
  }

  yield put(mediafiles.update(fileUpdate))
}

function* handleVerify(api: PodloveApiClient, action: { type: string; payload: number }) {
  const episodeId: number = yield select(selectors.episode.id)
  const assetId = action.payload

  yield verifyEpisodeAsset(api, episodeId, assetId)
}

async function loadMeta(audio: HTMLAudioElement) {
  return new Promise<void>((resolve) => (audio.onloadedmetadata = () => resolve()))
}

async function fetchDuration(src: string) {
  var audio = new Audio()

  audio.setAttribute('preload', 'metadata')
  audio.setAttribute('src', src)
  audio.load()

  await loadMeta(audio)

  return audio.duration
}

export default function () {
  return function* () {
    yield takeFirst(mediafiles.INIT, mediafilesSaga)
  }
}
