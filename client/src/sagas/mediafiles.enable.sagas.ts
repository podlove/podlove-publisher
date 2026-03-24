import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { put, select } from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import * as episode from '@store/episode.store'
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

  // Update episode freeze status if it was returned from enable
  if (typeof result.slug_frozen !== 'undefined') {
    yield put(episode.update({ prop: 'slug_frozen', value: result.slug_frozen }))
  }
}

export function* handleDisable(api: PodloveApiClient, action: { type: string; payload: number }) {
  const episodeId: string = yield select(selectors.episode.id)
  const asset_id = action.payload

  yield api.put(`episodes/${episodeId}/media/${asset_id}/disable`, {})
}
