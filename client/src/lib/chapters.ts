import * as npt from './normalplaytime'
import { PodloveChapter } from '../types/chapters.types'

export function parseMp4Chapters(input: string): PodloveChapter[] {
  const pattern = /^([\d\.:]+)\s(.*)$/

  return input
    .trim()
    .split(/(\r?\n)/)
    .reduce(function (all: PodloveChapter[], chapter: string) {
      var matches = chapter.match(pattern)

      if (matches) {
        var time = npt.parse(matches[1])

        if (time !== null) {
          all.push({
            title: matches[2].trim(),
            start: time,
          })
        }
      }

      return all
    }, [])
}

export function parseAudacityChapters(input: string): PodloveChapter[] {
  const pattern = /^([\d\.,]+)\s+([\d\.,]+)\s+(.*)$/
  return input
    .trim()
    .split(/(\r\n|\r|\n)/)
    .reduce(function (all: PodloveChapter[], chapter: string) {
      var matches = chapter.match(pattern)

      if (matches) {
        var time = npt.parse(matches[1].replace(',', '.'))
        var title = matches[3].trim()

        if (time !== null) {
          all.push({
            title: title,
            start: time,
          })
        }
      }

      return all
    }, [])
}

export function parseHindeburgChapters(input: string) {
  const parser = new window.DOMParser()
  const xml = parser.parseFromString(input, 'text/xml')
  const chapterTags = xml.getElementsByTagName('Marker')

  let chapters = Array.from(chapterTags).reduce((result: PodloveChapter[], tag) => {
    if (
      !tag ||
      !tag.getAttribute('Type') ||
      tag.getAttribute('Type')?.toLowerCase() !== 'chapter'
    ) {
      return result
    }

    const start = npt.parse(tag.getAttribute('Time') || '')
    const title = tag.getAttribute('Name') || ''
    const href = tag.getAttribute('URL') || ''

    if (start !== null) {
      result.push({ start: start, title: title.trim(), ...(href ? { href: href.trim() } : {}) })
    }

    return result
  }, [])

  chapters.sort(function (chapterA, chapterB) {
    return chapterA.start - chapterB.start
  })

  return chapters
}

export function parsePodloveChapters(input: string): PodloveChapter[] {
  const parser = new window.DOMParser()
  const xml = parser.parseFromString(input, 'text/xml')
  const chapterTags = xml.getElementsByTagNameNS('http://podlove.org/simple-chapters', 'chapter')

  return Array.from(chapterTags).reduce((result: PodloveChapter[], tag) => {
    var start = npt.parse(tag.getAttribute('start') || '')
    var title = tag.getAttribute('title') || ''
    var href = tag.getAttribute('href') || ''
    var image = tag.getAttribute('image') || ''

    if (start !== null) {
      result.push({
        start: start,
        title: title.trim(),
        ...(href ? { href: href.trim() } : {}),
        ...(image ? { image: image.trim() } : {}),
      })
    }

    return result
  }, [])
}
