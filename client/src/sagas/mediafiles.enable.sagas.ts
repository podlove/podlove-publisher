import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { put, select } from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import { MediaFile } from '@store/mediafiles.store'

export function* handleEnable(api: PodloveApiClient, action: { type: string; payload: number }) {
  const episodeId: string = yield select(selectors.episode.id)
  const asset_id = action.payload

  const { result } = yield api.put(`episodes/${episodeId}/media/${asset_id}/enable`, {})

  const fileUpdate: Partial<MediaFile> = {
    asset_id: asset_id,
    url: result.file_url,
    size: result.file_size,
    enable: true,
  }

  yield put(mediafiles.update(fileUpdate))
}

export function* handleDisable(api: PodloveApiClient, action: { type: string; payload: number }) {
  const episodeId: string = yield select(selectors.episode.id)
  const asset_id = action.payload

  yield api.put(`episodes/${episodeId}/media/${asset_id}/disable`, {})
}
