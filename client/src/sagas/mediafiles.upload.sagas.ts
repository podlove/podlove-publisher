import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { call, put, select } from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import * as episode from '@store/episode.store'
import * as wordpress from '@store/wordpress.store'
import * as progress from '@store/progress.store'
import {
  createAndWatchProgressChannel,
  createProgressHandler,
  ProgressPayload,
} from './helper'
import { Action } from 'redux'
import { get } from 'lodash'
import axios, { AxiosResponse } from 'axios'
import { Channel } from 'redux-saga'

export function* selectMediaFromLibrary() {
  yield put(
    wordpress.selectMediaFromLibrary({ onSuccess: { type: mediafiles.SET_WORDPRESS_UPLOAD_URL } })
  )
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
export function* triggerPlusUpload(api: PodloveApiClient, action: Action): Generator<any, void, any> {
  const file = get(action, ['payload'])
  const progressKey = `plus-upload-${file.name}`
  const episodeId: string = yield select(selectors.episode.id)

  // Reset any previous progress for this file
  yield put(progress.resetProgress(progressKey))

  try {
    const uploadUrl = yield call(getUploadUrl, api, file.name, episodeId)
    const fileUrl = yield call(uploadFileToUrl, uploadUrl, file, progressKey)
    yield put(mediafiles.setUploadUrl(fileUrl))
    const completeResult = yield call(completeFileUpload, api, file.name, episodeId)

    console.log('completeResult', completeResult)
  } catch (error) {
    console.error('File upload failed:', error)
    yield put(
      progress.setProgressStatus({
        key: progressKey,
        status: 'error',
        message: error instanceof Error ? error.message : 'File upload failed',
      })
    )
  }
}

/**
 * Gets a pre-signed upload URL from the Plus API
 */
function* getUploadUrl(api: PodloveApiClient, filename: string, episodeId: string): Generator<any, string, any> {
  const { result: upload_url } = yield api.post(`plus/create_file_upload`, {
    filename,
    episode_id: episodeId,
  })

  if (!upload_url) {
    throw new Error('Failed to get upload URL')
  }

  return upload_url
}

/**
 * Uploads file to the provided URL with progress tracking
 */
function* uploadFileToUrl(uploadUrl: string, file: File, progressKey: string): Generator<any, string, any> {
  const progressChannel: Channel<ProgressPayload> = yield call(
    createAndWatchProgressChannel,
    handleProgressUpdate
  )

  const handleProgress = createProgressHandler(progressChannel)

  const response: AxiosResponse<any> = yield call(axios.put, uploadUrl, file, {
    headers: { 'Content-Type': file.type },
    onUploadProgress: handleProgress(progressKey),
  })

  const fileUrl = response.config.url?.split('?')[0]

  if (!fileUrl) {
    throw new Error('Failed to extract file URL from response')
  }

  return fileUrl
}

/**
 * Completes the file upload process via Plus API
 */
function* completeFileUpload(api: PodloveApiClient, filename: string, episodeId: string): Generator<any, any, any> {
  const { result: completeResult } = yield api.post(`plus/complete_file_upload`, {
    filename,
    episode_id: episodeId,
  })

  if (!completeResult) {
    throw new Error('Failed to complete file upload')
  }

  return completeResult
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

export function* setUploadMedia(api: PodloveApiClient, action: Action) {
  const url = get(action, ['payload'])
  const slug = getSlugFromUrl(url)
  const currentSlug: string = yield select(selectors.episode.slug)

  if (!currentSlug) {
    yield put(episode.update({ prop: 'slug', value: slug }))
    yield put(episode.quicksave())
  } else if (slug === currentSlug) {
    yield call(verifyAll, api)
  }
}

export function* useUploadAsSlug() {
  const url: string | null = yield select(selectors.mediafiles.wordpressUploadUrl)
  const slugFrozen: boolean = yield select(selectors.episode.slugFrozen)

  if (!url || slugFrozen) {
    return
  }

  const slug = getSlugFromUrl(url)

  if (!slug) {
    return
  }

  yield put(episode.update({ prop: 'slug', value: slug }))
  yield put(mediafiles.disableSlugAutogen())
  yield put(episode.quicksave())
}

export function* keepCurrentSlug(api: PodloveApiClient) {
  yield call(verifyAll, api)
  yield put(mediafiles.clearUploadUrl())
}

function getSlugFromUrl(url: string): string {
  const filename = url.split('?')[0].split('\\').pop()?.split('/').pop() || ''

  return decodeURIComponent(filename.split('.').slice(0, -1).join('.'))
}

// Import verifyAll from verification saga
import { verifyAll } from './mediafiles.verification.sagas'
