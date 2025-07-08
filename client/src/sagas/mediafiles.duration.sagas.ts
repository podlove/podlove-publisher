import { PodloveApiClient } from '@lib/api'
import { selectors } from '@store'
import { put, select } from 'redux-saga/effects'
import * as episode from '@store/episode.store'
import { MediaFile } from '@store/mediafiles.store'

export function* maybeUpdateDuration(api: PodloveApiClient) {
  const files: MediaFile[] = yield select(selectors.mediafiles.files)
  const duration: string = yield select(selectors.episode.duration)
  const enabledFiles = files.filter((file) => file.enable && file.size && file.url)
  const audioFiles = enabledFiles.filter((file) => file.url.match(/\.(mp3|mp4|m4a|ogg|oga|opus)$/))

  let newDuration

  if (audioFiles.length === 0) {
    newDuration = '0'
  } else {
    const url = audioFiles[0].url
    const result: number = yield fetchDuration(url)

    newDuration = result.toString()
  }

  if (parseFloat(duration) !== parseFloat(newDuration)) {
    yield put(episode.update({ prop: 'duration', value: newDuration }))
  }
}

async function loadMeta(audio: HTMLAudioElement) {
  return new Promise<void>((resolve) => (audio.onloadedmetadata = () => resolve()))
}

async function fetchDuration(src: string) {
  var audio = new Audio()

  audio.setAttribute('preload', 'metadata')
  audio.setAttribute('src', src)
  audio.load()

  await loadMeta(audio)

  return audio.duration
}
