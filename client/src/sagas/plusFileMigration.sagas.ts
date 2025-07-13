import { takeFirst } from '../sagas/helper'
import { fork, put, select, call } from 'redux-saga/effects'
import { PodloveApiClient } from '@lib/api'
import { createApi } from '../sagas/api'

import * as plusFileMigration from '@store/plusFileMigration.store'
import * as auphonic from '@store/auphonic.store'
import { selectors } from '@store'

function* plusFileMigrationSaga() {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)
}

function* initialize(api: PodloveApiClient): Generator<any, void, any> {
  const { result: migrationStatusResult } = yield api.get(`plus/get_migration_status`)
  yield put(
    plusFileMigration.setMigrationComplete({
      isMigrationComplete: migrationStatusResult.is_complete,
    })
  )

  const { result } = yield api.get(`admin/plus/episodes_for_migration`)

  const episodesWithFiles: plusFileMigration.EpisodeWithFiles[] = result.episodes.map(
    (episode: any) => {
      return {
        episodeName: episode.episode_title,
        files: episode.files.map((file: any) => {
          return {
            name: file.filename,
            localUrl: file.local_url,
            remoteUrl: file.plus_url,
            state: 'init',
          }
        }),
      }
    }
  )

  yield put(plusFileMigration.setEpisodesWithFiles({ episodesWithFiles }))
  yield put(plusFileMigration.setTotalState({ totalState: 'ready' }))

  yield takeFirst(plusFileMigration.START_MIGRATION, startMigration, api)
}

function* migrateFile(
  api: PodloveApiClient,
  episodeIndex: number,
  fileIndex: number
): Generator<any, void, any> {
  const episodesWithFiles: plusFileMigration.EpisodeWithFiles[] = yield select(
    selectors.plusFileMigration.episodesWithFiles
  )

  const currentEpisode = episodesWithFiles[episodeIndex]
  const currentFile = currentEpisode.files[fileIndex]
  const currentEpisodeName = currentEpisode.episodeName
  const currentFileName = currentFile.name

  yield put(
    plusFileMigration.setCurrentMetadata({
      currentEpisodeName: currentEpisodeName,
      currentFileName: currentFileName,
    })
  )

  yield put(plusFileMigration.setFileState({ filename: currentFileName, state: 'in_progress' }))

  try {
    const response = yield api.post(`plus/migrate_file`, {
      filename: currentFileName,
      file_url: currentFile.localUrl,
    })

    if (response.result === false) {
      yield put(plusFileMigration.setFileState({ filename: currentFileName, state: 'error' }))
    } else {
      yield put(plusFileMigration.setFileState({ filename: currentFileName, state: 'finished' }))

      // Set auphonic transfer status to completed for UI consistency
      yield put(auphonic.setPlusTransferStatus({
        production_uuid: 'migration',
        status: 'completed'
      }))
    }

  } catch (error) {
    yield put(plusFileMigration.setFileState({ filename: currentFileName, state: 'error' }))
    throw error
  }
}

function* startMigration(api: PodloveApiClient): Generator<any, void, any> {
  yield put(plusFileMigration.setTotalState({ totalState: 'in_progress' }))

  const episodesWithFiles: plusFileMigration.EpisodeWithFiles[] = yield select(
    selectors.plusFileMigration.episodesWithFiles
  )

  const totalFiles = episodesWithFiles.reduce((acc, episode) => acc + episode.files.length, 0)
  let migratedFiles = 0
  let hasErrors = false

  const allMigrationTasks = episodesWithFiles.flatMap((episode, episodeIndex) =>
    episode.files.map((file, fileIndex) => ({ episodeIndex, fileIndex }))
  )

  for (const task of allMigrationTasks) {
    try {
      yield call(migrateFile, api, task.episodeIndex, task.fileIndex)
      migratedFiles++
    } catch (error) {
      hasErrors = true
      console.error('Error migrating file:', error)
    } finally {
      const progress = Math.round((migratedFiles / totalFiles) * 100)
      yield put(plusFileMigration.setProgress({ progress }))
    }
  }

  yield put(
    plusFileMigration.setTotalState({
      totalState: hasErrors ? 'error' : 'finished',
    })
  )

  if (!hasErrors) {
    yield api.post('plus/set_migration_complete', {})
  }
}

export default function () {
  return function* () {
    yield takeFirst(plusFileMigration.INIT, plusFileMigrationSaga)
  }
}
