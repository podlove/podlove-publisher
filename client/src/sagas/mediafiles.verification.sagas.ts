import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { all, fork, put, select } from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import * as episode from '@store/episode.store'
import { MediaFile } from '@store/mediafiles.store'

export function* verifyAll(api: PodloveApiClient) {
  const episodeId: number = yield select(selectors.episode.id)
  const mediaFiles: MediaFile[] = yield select(selectors.mediafiles.files)

  // verify all
  yield all(mediaFiles.map((file) => fork(verifyEpisodeAsset, api, episodeId, file.asset_id)))
}

function* verifyEpisodeAsset(api: PodloveApiClient, episodeId: number, assetId: number) {
  const mediaFiles: MediaFile[] = yield select(selectors.mediafiles.files)
  const prevMediaFile: MediaFile | undefined = mediaFiles.find((mf) => mf.asset_id == assetId)

  yield put(
    mediafiles.update({
      asset_id: assetId,
      is_verifying: true,
    })
  )

  const { result } = yield api.put(`episodes/${episodeId}/media/${assetId}/verify`, {})

  // auto-enable if file size changed from zero to non-zero
  const enable = (!prevMediaFile?.size && result.file_size) || prevMediaFile?.enable

  const fileUpdate: Partial<MediaFile> = {
    asset_id: assetId,
    url: result.file_url,
    size: result.file_size,
    enable: enable,
    is_verifying: false,
  }

  yield put(mediafiles.update(fileUpdate))

  // Update episode freeze status if it was returned from verification
  if (typeof result.slug_frozen !== 'undefined') {
    yield put(episode.update({ prop: 'slug_frozen', value: result.slug_frozen }))
  }
}

export function* handleVerify(api: PodloveApiClient, action: { type: string; payload: number }) {
  const episodeId: number = yield select(selectors.episode.id)
  const assetId = action.payload

  yield verifyEpisodeAsset(api, episodeId, assetId)
}
