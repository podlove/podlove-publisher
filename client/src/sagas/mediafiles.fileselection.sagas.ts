import { PodloveApiClient } from '@lib/api'
import { all, call, put } from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import * as episode from '@store/episode.store'
import { Action } from 'redux'
import { get } from 'lodash'

export function* handleFileSelection(api: PodloveApiClient, action: Action): Generator<any, void, any> {
  const { files, episodeSlug } = get(action, ['payload'])

  const currentSlug = yield call(setEpisodeSlugIfNeeded, files, episodeSlug)
  const fileInfos = createFileInfos(files, currentSlug)
  const fileInfosWithExistenceCheck = yield call(checkFileInfosExistence, api, fileInfos)

  yield put({
    type: mediafiles.SET_FILE_INFO,
    payload: fileInfosWithExistenceCheck,
  })
}

function extractSlugFromFilename(fileName: string): string {
  return fileName.split('.').slice(0, -1).join('.')
}

function* setEpisodeSlugIfNeeded(files: File[], providedSlug: string | null): Generator<any, string, any> {
  if (providedSlug) {
    return providedSlug
  }

  if (files.length === 0) {
    return ''
  }

  const firstFilename = files[0].name
  const extractedSlug = extractSlugFromFilename(firstFilename)

  yield put(episode.update({ prop: 'slug', value: extractedSlug }))

  return extractedSlug
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

function* checkFileInfosExistence(api: PodloveApiClient, fileInfos: any[]): Generator<any, any[], any> {
  return yield all(
    fileInfos.map((fileInfo) => call(checkFileExists, api, fileInfo))
  )
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
