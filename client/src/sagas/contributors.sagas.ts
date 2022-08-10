import { put } from 'redux-saga/effects'
import { get } from 'lodash'

import * as contributorsStore from '@store/contributors.store'
import { PodloveApiClient } from '@lib/api'

import { takeFirst } from './helper'
import { createApi } from './api'
import { PodloveContributor, PodloveGroup, PodloveRole } from '../types/contributors.types'

function* contributorsSaga() {
  const apiClient: PodloveApiClient = yield createApi()

  const [contributors, episodeContributors, groups, roles]: [
    { result: PodloveContributor[] },
    { result: PodloveContributor[] },
    { result: PodloveGroup[] },
    { result: PodloveRole[] },
  ] = yield Promise.all([
    apiClient.get('contributors'),
    apiClient.get('episodes/33/contributons'),
    apiClient.get('contributors/groups'),
    apiClient.get('contributors/roles'),
  ])
  if (contributors) {
    yield put(contributorsStore.set(get(contributors, ['result', 'contributors'], [])))
  }
  if (episodeContributors) {
    yield put(contributorsStore.set(get(episodeContributors, ['result', 'episodeContributors'], [])))
  }
  if (groups) {
    yield put(contributorsStore.setGroups(get(groups, ['result', 'groups'], [])))
  }
  if (roles) {
    yield put(contributorsStore.setRoles(get(roles, ['result', 'roles'], [])))
  }
}

export default function () {
  return function* () {
    yield takeFirst(contributorsStore.INIT, contributorsSaga)
  }
}

