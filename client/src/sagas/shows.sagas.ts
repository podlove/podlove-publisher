import { createApi } from './api'
import { PodloveApiClient } from '@lib/api'
import { fork, takeEvery } from '@redux-saga/core/effects'
import { select, put } from 'redux-saga/effects'
import { get } from 'lodash'
import { selectors } from '@store'
import { PodloveEpisodeList } from '../types/relatedEpisodes.types'
import { takeFirst } from './helper'

import * as showsStore from '@store/shows.store'

function* showsSaga(): any {
  console.log('shows saga init')
  const apiClient: PodloveApiClient = yield createApi()

  yield fork(initialize, apiClient)
}

function* initialize(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)

  // NOTE: There's a case to be made that clients should not have to deal with
  // the WP API. Instead, the Podlove REST API should provide all Shows details
  // and the fact that it's a taxonomy underneath is just an implementation
  // detail. Yeah, that sounds better.

  // todo: fetch all shows and selected show
  // todo: only init if shows module is on

  console.log('shows init', { episode: episodeId })
}

export default function () {
  return function* () {
    yield takeFirst(showsStore.INIT, showsSaga)
  }
}
