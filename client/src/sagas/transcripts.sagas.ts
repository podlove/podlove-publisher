import { fork } from '@redux-saga/core/effects'
import { takeEvery, select, put } from 'redux-saga/effects'
import { get } from 'lodash'
import { selectors } from '@store'
import { PodloveTranscript, PodloveTranscriptVoice } from '../types/transcripts.types'
import * as transcriptsStore from '@store/transcripts.store'
import { createApi } from './api'
import { PodloveApiClient } from '@lib/api'
import { notify } from '@store/notification.store'
import { takeFirst } from './helper'

function* transcriptsSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()

  yield fork(initialize, apiClient)
  yield takeEvery(transcriptsStore.IMPORT_TRANSCRIPTS, importTranscripts, apiClient)
  yield takeEvery(transcriptsStore.UPDATE_VOICE, updateVoice, apiClient)
  yield takeEvery(transcriptsStore.DELETE_TRANSCRIPTS, deleteTranscripts, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)

  const [transcripts, voices]: [
    { result: PodloveTranscript[] },
    { result: PodloveTranscriptVoice[] }
  ] = yield Promise.all([
    api.get(`transcripts/${episodeId}`),
    api.get(`transcripts/voices/${episodeId}`),
  ])

  yield put(transcriptsStore.setTranscripts(get(transcripts, ['result', 'transcript'], [])))
  yield put(transcriptsStore.setVoices(get(voices, ['result', 'voices'], [])))
}

function* importTranscripts(
  api: PodloveApiClient,
  action: { type: string, payload: string }
) {
  const episodeId: string = yield select(selectors.episode.id)
  const { result } = yield api.put(`transcripts/${episodeId}`, { content: action.payload })

  if (result) {
    yield fork(initialize, api)
  }
}

function* updateVoice(api: PodloveApiClient, action: { type: string, payload: { voice: string; contributor: string } }) {
  const episodeId: string = yield select(selectors.episode.id)

  yield api.post(`transcripts/voices/${episodeId}`, {
    voice: action.payload.voice,
    contributor_id: action.payload.contributor,
  })
}

function* deleteTranscripts(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)
  yield api.delete(`transcripts/${episodeId}`)
}

export default function () {
  return function* () {
    yield takeFirst(transcriptsStore.INIT, transcriptsSaga)
  }
}

