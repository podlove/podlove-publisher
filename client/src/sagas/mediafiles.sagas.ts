import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import {
  all,
  call,
  debounce,
  fork,
  put,
  select,
  takeEvery,
  takeLatest,
  throttle,
} from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import * as episode from '@store/episode.store'
import * as wordpress from '@store/wordpress.store'
import * as progress from '@store/progress.store'
import { MediaFile } from '@store/mediafiles.store'
import {
  createAndWatchProgressChannel,
  createProgressHandler,
  ProgressPayload,
  takeFirst,
} from './helper'
import { createApi } from './api'
import { Action } from 'redux'
import { get } from 'lodash'
import axios, { AxiosResponse } from 'axios'
import { Channel } from 'redux-saga'

function* mediafilesSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)
  const episodeSlug: string = yield select(selectors.episode.slug)
  const {
    result: { results: files },
  }: { result: { results: MediaFile[] } } = yield api.get(`episodes/${episodeId}/media`)

  if (files) {
    yield put(mediafiles.set(files))
  }

  yield takeEvery(mediafiles.ENABLE, handleEnable, api)
  yield takeEvery(mediafiles.DISABLE, handleDisable, api)
  yield takeEvery(mediafiles.VERIFY, handleVerify, api)
  yield takeLatest(episode.SLUG_CHANGED, verifyAll, api)
  yield debounce(2000, wordpress.UPDATE, maybeUpdateSlug, api)

  yield throttle(
    2000,
    [mediafiles.ENABLE, mediafiles.DISABLE, mediafiles.UPDATE],
    maybeUpdateDuration,
    api
  )
  yield takeEvery(mediafiles.UPLOAD_INTENT, selectMediaFromLibrary)
  yield takeEvery(mediafiles.PLUS_UPLOAD_INTENT, triggerPlusUpload, api)
  yield takeEvery(mediafiles.SET_UPLOAD_URL, setUploadMedia)

  yield put(mediafiles.initDone())
}

function* selectMediaFromLibrary() {
  yield put(wordpress.selectMediaFromLibrary({ onSuccess: { type: mediafiles.SET_UPLOAD_URL } }))
}

/**
 * Uploads a file to Podlove Plus service
 *
 * This saga:
 * 1. Requests a pre-signed upload URL from the Plus API
 * 2. Uploads the file directly to the provided URL
 * 3. Extracts the permanent file URL and dispatches it via setUploadUrl action
 * 4. Tracks upload progress
 */
function* triggerPlusUpload(api: PodloveApiClient, action: Action) {
  const file = get(action, ['payload'])
  const progressKey = `plus-upload-${file.name}`

  // Reset any previous progress for this file
  yield put(progress.resetProgress(progressKey))

  const { result: upload_url } = yield api.post(`plus/create_file_upload`, {
    filename: file.name,
  })

  if (!upload_url) {
    console.error('Failed to get upload URL')
    return
  }

  const progressChannel: Channel<ProgressPayload> = yield call(
    createAndWatchProgressChannel,
    handleProgressUpdate
  )

  const handleProgress = createProgressHandler(progressChannel)

  try {
    const response: AxiosResponse<any> = yield call(axios.put, upload_url, file, {
      headers: { 'Content-Type': file.type },
      onUploadProgress: handleProgress(progressKey),
    })

    const fileUrl = response.config.url?.split('?')[0]

    if (fileUrl) {
      yield put(mediafiles.setUploadUrl(fileUrl))
    }
  } catch (error) {
    console.error('File upload failed:', error)
    yield put(
      progress.setProgressStatus({
        key: progressKey,
        status: 'error',
        message: 'File upload failed',
      })
    )
  }
}

function* handleProgressUpdate(value: ProgressPayload) {
  yield put(progress.setProgress(value))

  yield put(
    progress.setProgressStatus({
      key: value.key,
      status: value.progress == 100 ? 'finished' : 'in_progress',
    })
  )
}

function* setUploadMedia(action: Action) {
  const url = get(action, ['payload'])
  const slug = url.split('\\').pop().split('/').pop().split('.').shift()

  // NOTE: maybe the the slug logic should be: if there is no slug, use the
  // filename. If there is a slug, use the slug (and rename the file on upload).
  // that would fix the issue of multiple assets and the local files have
  // different names, like episode-001.mp3 and transcript.txt.

  yield put(episode.update({ prop: 'slug', value: slug }))
  yield put(episode.quicksave())
}

function* maybeUpdateDuration(api: PodloveApiClient) {
  const files: MediaFile[] = yield select(selectors.mediafiles.files)
  const duration: string = yield select(selectors.episode.duration)
  const enabledFiles = files.filter((file) => file.enable && file.size && file.url)
  const audioFiles = enabledFiles.filter((file) => file.url.match(/\.(mp3|mp4|m4a|ogg|oga|opus)$/))

  let newDuration

  if (audioFiles.length === 0) {
    newDuration = '0'
  } else {
    const url = audioFiles[0].url
    const result: number = yield fetchDuration(url)

    newDuration = result.toString()
  }

  if (parseFloat(duration) !== parseFloat(newDuration)) {
    yield put(episode.update({ prop: 'duration', value: newDuration }))
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
    enable: true,
  }

  yield put(mediafiles.update(fileUpdate))
}

function* handleDisable(api: PodloveApiClient, action: { type: string; payload: number }) {
  const episodeId: string = yield select(selectors.episode.id)
  const asset_id = action.payload

  yield api.put(`episodes/${episodeId}/media/${asset_id}/disable`, {})
}

function* verifyAll(api: PodloveApiClient) {
  const episodeId: number = yield select(selectors.episode.id)
  const mediaFiles: MediaFile[] = yield select(selectors.mediafiles.files)

  // verify all
  yield all(mediaFiles.map((file) => fork(verifyEpisodeAsset, api, episodeId, file.asset_id)))
}

function* maybeUpdateSlug(
  api: PodloveApiClient,
  action: { type: string; payload: { prop: string; value: any } }
) {
  const episodeId: boolean = yield select(selectors.episode.id)
  const oldSlug: boolean = yield select(selectors.episode.slug)
  const enabled: boolean = yield select(selectors.mediafiles.slugAutogenerationEnabled)

  if (enabled && action.payload.prop == 'title' && action.payload.value) {
    const newTitle = action.payload.value

    const { result } = yield api.get(`episodes/${episodeId}/build_slug`, {
      query: { title: newTitle },
    })
    if (oldSlug != result.slug) {
      yield put(episode.update({ prop: 'slug', value: result.slug }))
    }
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
