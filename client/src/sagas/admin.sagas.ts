import { fork, put } from 'redux-saga/effects'
import { PodloveApiClient } from '@lib/api'
import { createApi } from './api'
import * as lifecycle from '../store/lifecycle.store'
import * as adminStore from '../store/admin.store'
import { takeFirst } from './helper'

interface AdminData {
    bannerHide: boolean | null
    type: string | null
    feedUrl: string | null
}

function* adminSaga() {
    const apiClient: PodloveApiClient = yield createApi()
    yield fork(initialize, apiClient)
}

function* initialize(api: PodloveApiClient) {
    const { result }: { result: AdminData } = yield api.get('admin/onboarding')

    if (result) {
        yield put(adminStore.set(result))
    }
}

export default function() {
    return function* () {
        yield takeFirst(adminStore.INIT, adminSaga)
    }
}