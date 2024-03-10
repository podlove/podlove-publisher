import { PodloveApiClient } from '@lib/api'
import { fork, put, select, takeEvery } from 'redux-saga/effects'
import { takeFirst } from './helper'
import { __ } from '../plugins/translations'
import { createApi } from './api'

import { selectors } from '@store'
import * as shows from '@store/shows.store'
import * as episode from '@store/episode.store'
import { PodloveShow } from '../types/shows.types'
import { get } from 'lodash'

function* showsSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const modules: string[] = yield select(selectors.settings.modules)
  const { result: showsResult }: { result: PodloveShow[] } = yield api.get(`shows`)

  if (shows) {
    yield put(shows.set(showsResult))

    if (modules.includes('automatic_numbering')) {
      yield takeEvery(episode.UPDATE, maybeUpdateEpisodeNumber, api)
    }
  }
}

function* maybeUpdateEpisodeNumber(
  api: PodloveApiClient,
  action: { type: string; payload: { prop: string; value: any } }
) {
  const prop = get(action, ['payload', 'prop'])
  const value = get(action, ['payload', 'value'], null)

  if (prop !== 'show') {
    return
  }

  const { result: number }: { result: number } = yield api.get(`shows/next_episode_number`, {
    query: { show: value },
  })

  yield put(episode.update({ prop: 'number', value: number.toString() }))
}

export default function () {
  return function* () {
    yield takeFirst(shows.INIT, showsSaga)
  }
}
