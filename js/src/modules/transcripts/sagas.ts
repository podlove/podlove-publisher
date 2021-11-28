import { fork, select, put } from '@redux-saga/core/effects'
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

  const [transcripts]: [{ result: PodloveTranscript[] }] = yield Promise.all([
    api.get(`transcripts/${episodeId}`),
    api.get(`transcripts/${episodeId}/voices`),
  ])

  if (transcripts.result) {
    yield put(transcriptsStore.setTranscripts(transcripts.result))
  }
}

sagas.run(transcriptsSaga)
