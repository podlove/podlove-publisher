import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { all, call, put, select } from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import * as episode from '@store/episode.store'

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
    // Recreate file infos with new slug
    const originalFiles = selectedFiles.map(fileInfo => {
      // Create a new File with the original name
      return new File([fileInfo.file], fileInfo.originalName, {
        type: fileInfo.file.type,
        lastModified: fileInfo.file.lastModified,
      })
    })

    const updatedFileInfos = createFileInfos(originalFiles, newSlug)

    // Check if files exist for each file with new names
    const fileInfosWithExistenceCheck = yield all(
      updatedFileInfos.map((fileInfo) => call(checkFileExists, api, fileInfo))
    )

    yield put({
      type: mediafiles.SET_FILE_INFO,
      payload: fileInfosWithExistenceCheck,
    })
  }
}

function* checkFileExists(api: PodloveApiClient, fileInfo: any): Generator<any, any, any> {
  const { result: fileExists } = yield api.post(`plus/check_file_exists`, {
    filename: fileInfo.file.name,
  })

  return {
    ...fileInfo,
    fileExists,
  }
}

/**
 * Creates file info objects with the original file names and the file names to
 * be used for the upload if an episode slug is provided.
 */
function createFileInfos(files: File[], episodeSlug?: string) {
  return files.map((file) => {
    if (!episodeSlug) {
      return {
        file,
        originalName: file.name,
        newName: file.name,
      }
    }

    const extension = file.name.split('.').pop()
    const newFileName = `${episodeSlug}.${extension}`

    const newFile = new File([file], newFileName, {
      type: file.type,
      lastModified: file.lastModified,
    })

    return {
      file: newFile,
      originalName: file.name,
      newName: newFile.name,
    }
  })
}
