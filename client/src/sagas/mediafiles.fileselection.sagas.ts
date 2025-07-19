import { PodloveApiClient } from '@lib/api'
import { call, put, select, delay, fork } from 'redux-saga/effects'
import * as mediafiles from '@store/mediafiles.store'
import * as episode from '@store/episode.store'
import * as progress from '@store/progress.store'
import { Action } from 'redux'
import { get } from 'lodash'
import { selectors } from '@store'

export function* handleFileSelection(api: PodloveApiClient, action: Action): Generator<any, void, any> {
  const { files, episodeSlug } = get(action, ['payload'])

  const existingSelectedFiles = yield select(selectors.mediafiles.selectedFiles)

  const existingFileObjects = existingSelectedFiles.map((fileInfo: any) => fileInfo.file)
  const newFiles = rejectExistingFiles(files, existingFileObjects)

  if (newFiles.length > 0) {
    const currentSlug = yield call(setEpisodeSlugIfNeeded, newFiles, episodeSlug)
    const episodeId = yield select(selectors.episode.id)

    // Immediately show files with original names (no file existence check yet)
    const immediateFileInfos = newFiles.map(file => ({
      file,
      originalName: file.name,
      newName: file.name,
      fileExists: null, // Will be determined after filename generation
    }))

    const allFileInfos = [...existingSelectedFiles, ...immediateFileInfos]

    // Show files immediately
    yield put({
      type: mediafiles.SET_FILE_INFO,
      payload: allFileInfos,
    })

    // Generate filenames in the background for each new file
    for (const file of newFiles) {
      yield fork(generateFilenameForFile, api, file, episodeId)
    }
  }
}

function rejectExistingFiles(files: File[], existingFiles: File[]): File[] {
  return files.filter((file: File) =>
    !existingFiles.some((existing: File) =>
      existing.name === file.name && existing.size === file.size
    )
  )
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

export function* checkFileExists(api: PodloveApiClient, fileInfo: any): Generator<any, any, any> {
  const { result: fileExists } = yield api.post(`plus/check_file_exists`, {
    filename: fileInfo.file.name,
  })

  return {
    ...fileInfo,
    fileExists,
  }
}

/**
 * Generate filename for a single file in the background and update the UI
 */
export function* generateFilenameForFile(api: PodloveApiClient, file: File, episodeId: string): Generator<any, void, any> {
  const progressKey = `filename-generation-${file.name}`

  try {
    // Start loading state
    yield put(progress.setProgressStatus({ key: progressKey, status: 'in_progress', message: 'Generating filename...' }))

    const { result } = yield api.post('plus/generate_filename', {
      original_filename: file.name,
      episode_id: episodeId,
    })

    const newFileName = result.generated_filename
    const newFile = new File([file], newFileName, {
      type: file.type,
      lastModified: file.lastModified,
    })

    // Check if file exists with the new filename
    const fileInfo = {
      file: newFile,
      originalName: file.name,
      newName: newFileName,
    }

    const fileInfoWithExistenceCheck = yield call(checkFileExists, api, fileInfo)

    // Update the specific file in the selectedFiles array
    yield call(updateFileInSelection, file.name, fileInfoWithExistenceCheck)

    // Complete loading state
    yield put(progress.setProgressStatus({ key: progressKey, status: 'finished', message: 'Filename generated' }))

    // Clean up progress state after a short delay
    yield fork(cleanupProgressState, progressKey, 2000)
  } catch (error) {
    // Error state
    yield put(progress.setProgressStatus({ key: progressKey, status: 'error', message: 'Failed to generate filename' }))

    // Clean up error state after a delay
    yield fork(cleanupProgressState, progressKey, 5000)

    console.warn('Failed to generate filename via API, keeping original:', error)
  }
}

/**
 * Update a specific file in the selectedFiles array
 */
export function* updateFileInSelection(originalFileName: string, updatedFileInfo: any): Generator<any, void, any> {
  const selectedFiles = yield select(selectors.mediafiles.selectedFiles)

  const updatedSelectedFiles = selectedFiles.map((fileInfo: any) =>
    fileInfo.originalName === originalFileName ? updatedFileInfo : fileInfo
  )

  yield put({
    type: mediafiles.SET_FILE_INFO,
    payload: updatedSelectedFiles,
  })
}

export function* cleanupProgressState(progressKey: string, delayMs: number): Generator<any, void, any> {
  yield delay(delayMs)
  yield put(progress.resetProgress(progressKey))
}
