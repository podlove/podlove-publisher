import { fork, put, select, takeEvery, throttle } from 'redux-saga/effects'
import { get, toInteger } from 'lodash'

import * as contributors from '@store/contributors.store'
import * as episode from '../store/episode.store'

import { PodloveApiClient } from '@lib/api'

import { takeFirst } from './helper'
import { createApi } from './api'
import { selectors } from '@store'
import { PodloveEpisode, PodloveEpisodeContribution } from '../types/episode.types'
import { Action } from 'redux'
import { __ } from '../plugins/translations'

function* contributorsSaga() {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(fetchContributors, apiClient)
  yield fork(fetchRoles, apiClient)
  yield fork(fetchGroups, apiClient)
  yield fork(fetchEpisodeContributions, apiClient)
  yield takeEvery(episode.CREATE_CONTRIBUTION, createEpisodeContribution, apiClient)
  yield throttle(
    3000,
    [
      episode.MOVE_CONTRIBUTION_DOWN,
      episode.MOVE_CONTRIBUTION_UP,
      episode.DELETE_CONTRIBUTION,
      episode.UPDATE_CONTRIBUTION,
      episode.ADD_CONTRIBUTION,
    ],
    updateEpisodeContributions,
    apiClient
  )
}

function* fetchEpisodeContributions(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)

  if (!episodeId) {
    return
  }

  const { result }: { result: PodloveEpisode } = yield api.get(
    `episodes/${episodeId}/contributions`
  )

  if (!result) {
    return
  }

  yield put(episode.set({ contributions: get(result, ['contribution'], []) }))
}

function* updateEpisodeContributions(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)

  if (!episodeId) {
    return
  }

  const data: PodloveEpisodeContribution[] = yield select(selectors.episode.contributions)
  const contributors = data.map(({ contributor_id, role_id, group_id, position, comment }) => ({
    contributor_id: toInteger(contributor_id),
    role_id: toInteger(role_id),
    group_id: toInteger(group_id),
    position: toInteger(position),
    comment: comment || '',
  }))

  yield api.put(`episodes/${episodeId}/contributions`, { contributors })
}

function* fetchContributors(api: PodloveApiClient) {
  const { result } = yield api.get('contributors', { query: { filter: 'all' } })

  if (!result) {
    return
  }

  yield put(contributors.setContributors(get(result, 'contributors', [])))
}

function* fetchRoles(api: PodloveApiClient) {
  const { result } = yield api.get('contributors/roles')

  if (!result) {
    return
  }

  yield put(contributors.setRoles(get(result, 'roles', [])))
}

function* fetchGroups(api: PodloveApiClient) {
  const { result } = yield api.get('contributors/groups')

  if (!result) {
    return
  }

  yield put(contributors.setGroups(get(result, 'groups', [])))
}

function* createEpisodeContribution(api: PodloveApiClient, action: Action) {
  const { result: createContributorResult, error: createContributorError } = yield api.post(
    `contributors`,
    {}
  )

  if (createContributorError) {
    return
  }

  const contributorId = createContributorResult?.id
  const realname: string = get(action, ['payload'])
  const { error: updateContributorError } = yield api.put(`contributors/${contributorId}`, { realname })

  if (updateContributorError) {
    return
  }

  const contributor = { id: contributorId, realname }

  yield put(contributors.addContributor(contributor))
  yield put(episode.addContribution(contributor))
}

export default function () {
  return function* () {
    yield takeFirst(contributors.INIT, contributorsSaga)
  }
}
