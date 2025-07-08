import { PodloveApiClient } from '@lib/api'
import { all, call, put } from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import * as episode from '@store/episode.store'
import { Action } from 'redux'
import { get } from 'lodash'

export function* handleFileSelection(api: PodloveApiClient, action: Action): Generator<any, void, any> {
  const { files, episodeSlug } = get(action, ['payload'])

  // Extract slug from first file if no episode slug is set
  let currentSlug = episodeSlug
  if (!currentSlug && files.length > 0) {
    const firstFileName = files[0].name
    // Remove extension and use as slug
    currentSlug = firstFileName.split('.').slice(0, -1).join('.')

    // Set the episode slug immediately
    yield put(episode.update({ prop: 'slug', value: currentSlug }))
  }

  const fileInfos = createFileInfos(files, currentSlug)

  // Check if files exist for each file
  const fileInfosWithExistenceCheck = yield all(
    fileInfos.map((fileInfo) => call(checkFileExists, api, fileInfo))
  )

  yield put({
    type: mediafiles.SET_FILE_INFO,
    payload: fileInfosWithExistenceCheck,
  })
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
