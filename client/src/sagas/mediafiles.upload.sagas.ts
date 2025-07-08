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
export function* triggerPlusUpload(api: PodloveApiClient, action: Action) {
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

      // Complete the file upload
      const { result: completeResult } = yield api.post(`plus/complete_file_upload`, {
        filename: file.name,
      })

      console.log('completeResult', completeResult)

      if (!completeResult) {
        console.error('Failed to complete file upload')
      }
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

export function* setUploadMedia(api: PodloveApiClient, action: Action) {
  const url = get(action, ['payload'])
  const slug = url.split('\\').pop().split('/').pop().split('.').shift()
  const currentSlug: string = yield select(selectors.episode.slug)

  if (!currentSlug) {
    yield put(episode.update({ prop: 'slug', value: slug }))
    yield put(episode.quicksave())
  } else {
    // If slug is already set, verify the media files, which is otherwise a side
    // effect of saving the episode
    yield call(verifyAll, api)
  }
}

// Import verifyAll from verification saga
import { verifyAll } from './mediafiles.verification.sagas'
