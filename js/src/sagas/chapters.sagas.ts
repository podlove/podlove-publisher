import { TakeableChannel } from '@redux-saga/core'
import { select, takeEvery, call, put } from 'redux-saga/effects'
import keyboard from '@podlove/utils/keyboard'
import { selectors } from '@store'
import * as lifecycle from '@store/lifecycle.store'
import * as chapters from '@store/chapters.store'
import { PodloveChapter } from '@types/chapters.types'
import Timestamp from '@lib/timestamp'

import { channel } from './helper'

import MP4Chaps from 'podcast-chapter-parser-mp4chaps'
import Audacity from 'podcast-chapter-parser-audacity'
import Hindenburg from 'podcast-chapter-parser-hindenburg'
import Psc from 'podcast-chapter-parser-psc'

function chaptersSaga(episodeForm: HTMLElement) {
  const chaptersForm = <HTMLTextAreaElement>document.createElement('textarea')
  chaptersForm.setAttribute('name', '_podlove_meta[chapters]')
  chaptersForm.style.display = 'none'
  episodeForm.append(chaptersForm)

  return function* () {
    yield takeEvery([lifecycle.INIT, chapters.UPDATE, chapters.REMOVE], syncForm, chaptersForm)
    yield takeEvery([chapters.PARSE], parseChapters)
    yield takeEvery(chapters.DOWNLOAD, handleDownload)
    const onKeyDown: TakeableChannel<any> = yield call(channel, keyboard.utils.keydown)
    yield takeEvery(onKeyDown, handleKeydown)
  }
}

function* syncForm(form: HTMLTextAreaElement) {
  const chapters: PodloveChapter[] = yield select(selectors.chapters.list)
  form.value = chapters.map(({ start, title }) => `${start} ${title}`).join(' ')
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
    node.setAttribute('title', chapter.title)
    node.setAttribute('start', new Timestamp(chapter.start).pretty)

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
        let line = new Timestamp(chapter.start).pretty + ' ' + chapter.title

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

function* handleDownload(action: typeof chapters.download) {
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

function* parseChapters(action: typeof chapters.parse) {
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
    console.log('Unable to parse PSC chapters.')
    return
  }

  yield put(chapters.set(parsedChapters))
}

export default chaptersSaga
