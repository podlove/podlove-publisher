import { fork, select, put } from '@redux-saga/core/effects'
import { get } from 'lodash'
import { selectors, sagas } from '@store'
import { PodloveTranscript } from '@types/transcripts.types'
import * as transcriptsStore from '@store/transcripts.store'
import { createApi } from '../../sagas/api'

function* transcriptsSaga() {
  const nonce = yield select(selectors.runtime.nonce)
  const apiClient = yield createApi()

  yield fork(initialize, apiClient)
}

function* initialize(api) {
  const episodeId = yield select(selectors.episode.id)

  const [transcripts, voices]: [{ result: PodloveTranscript[] }] = yield Promise.all([
    api.get(`transcripts/${episodeId}`),
    api.get(`transcripts/voices/${episodeId}`),
  ])

  yield put(transcriptsStore.setTranscripts(get(transcripts, ['result', 'transcript'], [])))
  yield put(transcriptsStore.setVoices(get(voices, ['result', 'voices'], [])))
}

sagas.run(transcriptsSaga)
