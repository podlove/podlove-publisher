import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { fork, put, select, takeEvery, throttle } from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import * as episode from '@store/episode.store'
import { MediaFile } from '@store/mediafiles.store'
import { takeFirst } from './helper'
import { createApi } from './api'

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
  yield throttle(
    2000,
    [mediafiles.ENABLE, mediafiles.DISABLE, mediafiles.UPDATE],
    maybeUpdateDuration,
    api
  )

  yield put(mediafiles.initDone())
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

function* handleVerify(api: PodloveApiClient, action: { type: string; payload: number }) {
  const episodeId: string = yield select(selectors.episode.id)
  const asset_id = action.payload

  const { result } = yield api.put(`episodes/${episodeId}/media/${asset_id}/verify`, {})

  const fileUpdate: Partial<MediaFile> = {
    asset_id: asset_id,
    url: result.file_url,
    size: result.file_size,
  }

  yield put(mediafiles.update(fileUpdate))
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
