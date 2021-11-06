import { fork, takeEvery, select, put } from '@redux-saga/core/effects'
import { selectors, sagas } from '@store'
import { restClient } from '@lib/api'

import * as transcriptsStore from '@store/transcripts.store'


function* transcriptsSaga() {
  const nonce = yield select(selectors.runtime.nonce)

  const api = restClient({ nonce })

  yield fork(initialize, api);
}

function* initialize(api) {
  const post = yield select(selectors.post.id)

  const result = yield api.get('/', { query: {
    p: post,
    podlove_transcript: 'json_grouped'
  } })

  yield put(transcriptsStore.set(result))
}

sagas.run(transcriptsSaga)
