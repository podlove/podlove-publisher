import { fork, select, put, takeEvery } from 'redux-saga/effects'
import { PodloveApiClient } from '@lib/api'
import { createApi } from './api'
import * as adminStore from '@store/admin.store'
import { takeFirst } from './helper'
import { selectors } from '@store'

interface AdminData {
    bannerHide: boolean | null
    type: string | null
    feedUrl: string | null
}

function* adminSaga() {
    const apiClient: PodloveApiClient = yield createApi()
    yield fork(initialize, apiClient)
    yield takeEvery(adminStore.UPDATE_TYPE, save, apiClient)
}

function* initialize(api: PodloveApiClient) {
    const { result }: { result: AdminData } = yield api.get('admin/onboarding')

    if (result) {
        yield put(adminStore.set(result))
    }
}

function* save(api: PodloveApiClient, action: {type: string}) {
    const typeValue: string = yield select(selectors.admin.type)

    yield api.put('admin/onboarding', {type: typeValue})
}

export default function() {
    return function* () {
        yield takeFirst(adminStore.INIT, adminSaga)
    }
}