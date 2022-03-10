import { TakeableChannel } from '@redux-saga/core'
import { select, takeEvery, call, put, fork } from 'redux-saga/effects'

import MP4Chaps from 'podcast-chapter-parser-mp4chaps'
import Audacity from 'podcast-chapter-parser-audacity'
import Hindenburg from 'podcast-chapter-parser-hindenburg'
import Psc from 'podcast-chapter-parser-psc'

import keyboard from '@podlove/utils/keyboard'
import { selectors } from '@store'
import Timestamp from '@lib/timestamp'
import { PodloveApiClient } from '@lib/api'
import { notify } from '@store/notification.store'
import * as chapters from '@store/chapters.store'

import { PodloveChapter } from '../types/chapters.types'
import { channel, takeFirst } from '../sagas/helper'
import { createApi } from '../sagas/api'

function* chaptersSaga(): any {
  const apiClient: PodloveApiClient = yield createApi()
  yield fork(initialize, apiClient)

  yield takeEvery([chapters.PARSE], handleImport)
  yield takeEvery(chapters.DOWNLOAD, handleExport)
  yield takeEvery(
    [chapters.UPDATE, chapters.PARSED, chapters.ADD, chapters.REMOVE],
    save,
    apiClient
  )
  const onKeyDown: TakeableChannel<any> = yield call(channel, keyboard.utils.keydown)

  yield takeEvery(onKeyDown, handleKeydown)
}

function* initialize(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)
  const { result }: { result: { chapters: PodloveChapter[] } } = yield api.get(
    `chapters/${episodeId}`
  )

  if (result) {
    yield put(
      chapters.set(
        result.chapters.map((chapter) => ({
          ...chapter,
          start: Timestamp.fromString(chapter.start).totalMs,
        }))
      )
    )
  }
}

function* save(api: PodloveApiClient) {
  const episodeId: string = yield select(selectors.episode.id)
  const chapters: PodloveChapter[] = yield select(selectors.chapters.list)

  yield api.put(`chapters/${episodeId}`, {
    chapters: chapters.map((chapter) => ({
      ...chapter,
      start: new Timestamp(chapter.start).pretty,
    })),
  })
}

// Export handling
function* handleExport(action: { type: string; payload: 'psc' | 'mp4' }) {
  const chapters: PodloveChapter[] = yield select(selectors.chapters.list)

  switch (action.payload) {
    case 'psc':
      download('chapters.psc', generatePscDownload(chapters))
      break
    case 'mp4':
      download('chapters.txt', generateMp4Download(chapters))
      break
  }
}

function generatePscDownload(chapters: PodloveChapter[]): string {
  const serializer = new XMLSerializer()

  const psc = '<psc:chapters version="1.2" xmlns:psc="http://podlove.org/simple-chapters"/>'
  const parser = new DOMParser()
  const xmlDoc = parser.parseFromString(psc, 'text/xml')

  // need both tries for Chrome/Firefox compatibility
  let pscDoc: any = xmlDoc.getElementsByTagName('chapters')

  if (!pscDoc.length) {
    pscDoc = xmlDoc.getElementsByTagName('psc:chapters')
  }

  pscDoc = pscDoc[0]

  chapters.forEach((chapter: PodloveChapter) => {
    let node = xmlDoc.createElement('psc:chapter')
    node.setAttribute('title', chapter.title || '')
    node.setAttribute('start', chapter.start ? new Timestamp(chapter.start).pretty : '')

    if (chapter.href) {
      node.setAttribute('href', chapter.href)
    }

    pscDoc.appendChild(node)
  })

  let serialized = serializer.serializeToString(xmlDoc)

  // poor man's formatting
  let formatted = serialized
    .replace(/\<psc:chapter\s/gi, '\n    <psc:chapter ')
    .replace('</psc:chapters>', '\n</psc:chapters>')

  return formatted
}

function generateMp4Download(chapters: PodloveChapter[]): string {
  return (
    chapters
      .reduce((result: string[], chapter) => {
        let line = chapter.start ? new Timestamp(chapter.start).pretty + ' ' : ''
        line += chapter.title

        if (chapter.href) {
          line = line + ' <' + chapter.href + '>'
        }

        return [...result, line]
      }, [])
      .join('\n') + '\n'
  )
}

function download(name: string, data: any) {
  var blob = new Blob([data], { type: 'text/plain' })
  const a = document.createElement('a')
  a.href = window.URL.createObjectURL(blob)
  a.download = name
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
}

// Import handling
function* handleImport(action: { type: string; payload: string }) {
  const parser: ((text: string) => PodloveChapter[])[] = [
    MP4Chaps.parse,
    Audacity.parse,
    Hindenburg.parser(window.DOMParser).parse,
    Psc.parser(window.DOMParser).parse,
  ]

  let parsedChapters: PodloveChapter[] | null = []

  parser.forEach((parseFn) => {
    if (parsedChapters !== null && parsedChapters.length > 0) {
      return
    }

    try {
      parsedChapters = parseFn(action.payload)
    } catch (err) {
      parsedChapters = null
    }
  })

  if (parsedChapters === null) {
    yield put(notify({ type: 'error', message: 'Unable to parse PSC chapters.' }))
    return
  }

  yield put(chapters.parsed(parsedChapters))
}

// Key event handling
function* handleKeydown(input: {
  key: string
  ctrl: boolean
  shift: boolean
  meta: boolean
  alt: boolean
}) {
  const selectedIndex: number = yield select(selectors.chapters.selectedIndex)

  if (selectedIndex === null) {
    return
  }

  const chaptersList: PodloveChapter[] = yield select(selectors.chapters.list)

  switch (true) {
    case input.key === 'up':
      if (selectedIndex === 0) {
        yield put(chapters.select(chaptersList.length - 1))
      } else {
        yield put(chapters.select(selectedIndex - 1))
      }
      break
    case input.key === 'down':
      if (selectedIndex === chaptersList.length - 1) {
        yield put(chapters.select(0))
      } else {
        yield put(chapters.select(selectedIndex + 1))
      }
      break
    case input.key === 'esc':
      yield put(chapters.select(null))
      break
  }
}

export default function () {
  return function* () {
    yield takeFirst(chapters.INIT, chaptersSaga)
  }
}
