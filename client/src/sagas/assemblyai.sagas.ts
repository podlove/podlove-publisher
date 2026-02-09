import { put, fork, select, call, takeEvery } from 'redux-saga/effects'
import { selectors } from '@store'
import * as assemblyaiStore from '@store/assemblyai.store'
import * as transcriptsStore from '@store/transcripts.store'
import { createApi } from './api'
import { PodloveApiClient } from '@lib/api'
import { takeFirst, sleep } from './helper'

function* assemblyaiSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)
  yield takeEvery(assemblyaiStore.START_TRANSCRIPTION, handleStartTranscription, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const { result } = yield api.get('assemblyai/config')

  if (result) {
    yield put(assemblyaiStore.setHasApiKey(result.has_api_key))
  }

  // Restore status from post meta if there's an ongoing transcription
  const postId: string = yield select(selectors.post.id)
  if (!postId) return

  const { result: statusResult } = yield api.get(`assemblyai/status/${postId}`)

  if (statusResult && statusResult.status) {
    const status = statusResult.status

    if (status === 'queued' || status === 'processing') {
      yield put(assemblyaiStore.setStatus('processing'))
      yield put(assemblyaiStore.setAssemblyAIStatus(status))
      yield fork(pollTranscriptionStatus, api)
    } else if (status === 'completed') {
      yield put(assemblyaiStore.setStatus('importing'))
      yield put(assemblyaiStore.setAssemblyAIStatus('completed'))
      yield* handleImportTranscript(api)
    } else if (status === 'imported') {
      yield put(assemblyaiStore.setStatus('imported'))
    }
  }
}

function* handleStartTranscription(api: PodloveApiClient) {
  const postId: string = yield select(selectors.post.id)
  if (!postId) return

  yield put(assemblyaiStore.setStatus('submitting'))
  yield put(assemblyaiStore.setError(null))

  const { result, error } = yield api.post(`assemblyai/transcribe/${postId}`, {})

  if (error) {
    yield put(assemblyaiStore.setError(error.error || 'Failed to start transcription'))
    yield put(assemblyaiStore.setStatus('error'))
    return
  }

  yield put(assemblyaiStore.setTranscriptId(result.transcript_id))
  yield put(assemblyaiStore.setStatus('processing'))
  yield put(assemblyaiStore.setAssemblyAIStatus(result.status))

  yield fork(pollTranscriptionStatus, api)
}

const MAX_POLL_ATTEMPTS = 360 // 30 minutes at 5s intervals

function* pollTranscriptionStatus(api: PodloveApiClient) {
  const postId: string = yield select(selectors.post.id)

  for (let attempt = 0; attempt < MAX_POLL_ATTEMPTS; attempt++) {
    yield call(sleep, 5)

    const { result, error } = yield api.get(`assemblyai/status/${postId}`)

    if (error) {
      yield put(assemblyaiStore.setError('Failed to check transcription status'))
      yield put(assemblyaiStore.setStatus('error'))
      return
    }

    const status = result.status
    yield put(assemblyaiStore.setAssemblyAIStatus(status))

    if (status === 'completed') {
      yield put(assemblyaiStore.setStatus('importing'))
      yield* handleImportTranscript(api)
      return
    }

    if (status === 'error') {
      yield put(assemblyaiStore.setError(result.error || 'Transcription failed'))
      yield put(assemblyaiStore.setStatus('error'))
      return
    }
  }

  yield put(assemblyaiStore.setError('Transcription timed out. Please try again.'))
  yield put(assemblyaiStore.setStatus('error'))
}

function* handleImportTranscript(api: PodloveApiClient) {
  const postId: string = yield select(selectors.post.id)
  if (!postId) return

  yield put(assemblyaiStore.setStatus('importing'))
  yield put(assemblyaiStore.setError(null))

  const { result, error } = yield api.post(`assemblyai/import/${postId}`, {})

  if (error) {
    yield put(assemblyaiStore.setError(error.error || 'Failed to import transcript'))
    yield put(assemblyaiStore.setStatus('error'))
    return
  }

  yield put(assemblyaiStore.setStatus('imported'))

  // Refresh transcripts store so the transcript appears in the UI
  yield put(transcriptsStore.refresh())
}

export default function () {
  return function* () {
    yield takeFirst(assemblyaiStore.INIT, assemblyaiSaga)
  }
}
