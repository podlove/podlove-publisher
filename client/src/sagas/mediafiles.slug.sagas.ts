import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { put, select, fork } from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import * as episode from '@store/episode.store'
import { generateFilenameForFile } from './mediafiles.fileselection.sagas'

export function* maybeUpdateSlug(
  api: PodloveApiClient,
  action: { type: string; payload: { prop: string; value: any } }
) {
  const episodeId: boolean = yield select(selectors.episode.id)
  const oldSlug: boolean = yield select(selectors.episode.slug)
  const enabled: boolean = yield select(selectors.mediafiles.slugAutogenerationEnabled)

  if (enabled && action.payload.prop == 'title' && action.payload.value) {
    const newTitle = action.payload.value

    const { result } = yield api.get(`episodes/${episodeId}/build_slug`, {
      query: { title: newTitle },
    })
    if (oldSlug != result.slug) {
      yield put(episode.update({ prop: 'slug', value: result.slug }))
    }
  }
}

export function* updateSelectedFileNames(api: PodloveApiClient): Generator<any, void, any> {
  const selectedFiles: any[] = yield select(selectors.mediafiles.selectedFiles)
  const newSlug: string = yield select(selectors.episode.slug)

  if (selectedFiles.length > 0 && newSlug) {
    // Recreate file infos with original names first
    const originalFiles = selectedFiles.map(fileInfo => {
      return new File([fileInfo.file], fileInfo.originalName, {
        type: fileInfo.file.type,
        lastModified: fileInfo.file.lastModified,
      })
    })

    // Immediately update files with original names (no file existence check yet)
    const immediateFileInfos = originalFiles.map(file => ({
      file,
      originalName: file.name,
      newName: file.name,
      fileExists: null, // Will be determined after filename generation
    }))

    // Show files immediately
    yield put({
      type: mediafiles.SET_FILE_INFO,
      payload: immediateFileInfos,
    })

    // Generate new filenames in the background
    const episodeId = yield select(selectors.episode.id)
    for (const file of originalFiles) {
      yield fork(generateFilenameForFile, api, file, episodeId)
    }
  }
}
