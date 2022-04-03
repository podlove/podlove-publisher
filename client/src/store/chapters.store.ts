import { get } from 'lodash'
import { handleActions, createAction } from 'redux-actions'
import { PodloveChapter } from '../types/chapters.types'

export type State = {
  chapters: PodloveChapter[]
  selected: number | null
}

export const initialState: State = {
  chapters: [],
  selected: null,
}

export const INIT = 'podlove/publisher/chapter/INIT'
export const UPDATE = 'podlove/publisher/chapter/UPDATE'
export const SELECT = 'podlove/publisher/chapter/SELECT'
export const REMOVE = 'podlove/publisher/chapter/REMOVE'
export const ADD = 'podlove/publisher/chapter/ADD'
export const PARSE = 'podlove/publisher/chapter/PARSE'
export const PARSED = 'podlove/publisher/chapter/PARSED'
export const SET = 'podlove/publisher/chapter/SET'
export const DOWNLOAD = 'podlove/publisher/chapter/DOWNLOAD'
export const SELECT_IMAGE = 'podlove/publisher/chapter/SELECT_IMAGE'
export const SET_IMAGE = 'podlove/publisher/chapter/SET_IMAGE'

export const init = createAction<void>(INIT)
export const update = createAction<{ chapter: Partial<PodloveChapter>; index: number }>(UPDATE)
export const select = createAction<number | null>(SELECT)
export const remove = createAction<number>(REMOVE)
export const add = createAction<void>(ADD)
export const parse = createAction<string>(PARSE)
export const parsed = createAction<PodloveChapter[]>(PARSED)
export const set = createAction<PodloveChapter[]>(SET)
export const download = createAction<'psc' | 'mp4'>(DOWNLOAD)
export const selectImage = createAction<void>(SELECT_IMAGE)
export const setImage = createAction<string>(SET_IMAGE)

export const reducer = handleActions(
  {
    [PARSED]: (state: State, action: typeof parsed): State => ({
      ...state,
      selected: null,
      chapters: get(action, ['payload'], []) as PodloveChapter[],
    }),
    [SET]: (state: State, action: typeof set): State => ({
      ...state,
      selected: null,
      chapters: get(action, ['payload'], []) as PodloveChapter[],
    }),
    [UPDATE]: (
      state: State,
      action: { type: string; payload: { chapter: Partial<PodloveChapter>; index: number } }
    ): State => {
      let selectedChapterIndex = selectedIndex(state)
      const selectedChapter = selected(state)

      // update chapter
      const chapters = state.chapters.reduce(
        (result: PodloveChapter[], chapter, chapterIndex) => [
          ...result,
          chapterIndex === action.payload.index
            ? { ...chapter, ...action.payload.chapter }
            : chapter,
        ],
        []
      )

      // sort chapters by time
      const sortedChapters = chapters.sort((a, b) => a.start - b.start)

      // update selected index
      const newSelectedIndex = sortedChapters.findIndex(
        (chapter) => selectedChapter && chapter.title == selectedChapter.title
      )

      return {
        ...state,
        chapters: sortedChapters,
        selected: newSelectedIndex >= 0 ? newSelectedIndex : selectedChapterIndex,
      }
    },
    [SELECT]: (state: State, action: { type: string; payload: number }): State => ({
      ...state,
      selected: action.payload,
    }),
    [ADD]: (state: State): State => ({
      ...state,
      chapters: [
        ...state.chapters,
        {
          start: get(state, ['chapters', state.chapters.length - 1, 'start'], 0),
          title: '',
          href: '',
          image: '',
        },
      ],
    }),
    [REMOVE]: (state: State, action: { type: string; payload: number }): State => ({
      ...state,
      selected: null,
      chapters: state.chapters.filter((chapter, index) => index !== action.payload),
    }),
    [SET_IMAGE]: (state: State, action: { type: string; payload: string }): State => ({
      ...state,
      chapters: state.chapters.map((chapter, index) => {
        if (index !== state.selected) {
          return chapter
        }

        return {
          ...chapter,
          image: action.payload
        }
      })
    })
  },
  initialState
)

const chapters = (state: State) => state.chapters

const selectedIndex = (state: State) => state.selected

const selected = (state: State) =>
  state.selected !== null ? get(state, ['chapters', state.selected], null) : null

export const selectors = {
  chapters,
  selectedIndex,
  selected,
}
