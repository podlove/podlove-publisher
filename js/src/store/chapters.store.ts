import { get } from 'lodash'
import { handleActions, createAction } from 'redux-actions'
import { PodloveChapter } from '@types/chapters.types'
import { INIT, init } from './lifecycle.store'

export type State = {
  chapters: PodloveChapter[]
  selected: number | null
}

export const initialState: State = {
  chapters: [],
  selected: null,
}

export const UPDATE = 'podlove/publisher/chapter/UPDATE'
export const SELECT = 'podlove/publisher/chapter/SELECT'
export const REMOVE = 'podlove/publisher/chapter/REMOVE'
export const ADD = 'podlove/publisher/chapter/ADD'
export const PARSE = 'podlove/publisher/chapter/PARSE'
export const PARSED = 'podlove/publisher/chapter/PARSED'
export const SET = 'podlove/publisher/chapter/SET'
export const DOWNLOAD = 'podlove/publisher/chapter/DOWNLOAD'

export const update = createAction<{ chapter: Partial<PodloveChapter>; index: number }>(UPDATE)
export const select = createAction<number>(SELECT)
export const remove = createAction<number>(REMOVE)
export const add = createAction<void>(ADD)
export const parse = createAction<string>(PARSE)
export const parsed = createAction<PodloveChapter[]>(PARSED)
export const set = createAction<PodloveChapter[]>(SET)
export const download = createAction<'psc' | 'mp4'>(DOWNLOAD)

export const reducer = handleActions(
  {
    [PARSED]: (state: State, action: typeof init): State => ({
      ...state,
      selected: null,
      chapters: get(action, ['payload'], []),
    }),
    [SET]: (state: State, action: typeof init): State => ({
      ...state,
      selected: null,
      chapters: get(action, ['payload'], []),
    }),
    [UPDATE]: (state: State, action: typeof update): State => ({
      ...state,
      chapters: state.chapters.reduce(
        (result: PodloveChapter[], chapter, chapterIndex) => [
          ...result,
          chapterIndex === action.payload.index
            ? { ...chapter, ...action.payload.chapter }
            : chapter,
        ],
        []
      ),
    }),
    [SELECT]: (state: State, action: typeof select): State => ({
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
    [REMOVE]: (state: State, action: typeof remove): State => ({
      ...state,
      selected: null,
      chapters: state.chapters.filter((chapter, index) => index !== action.payload),
    }),
  },
  initialState
)

export const selectors = {
  chapters: (state: State) => state.chapters,
  selectedIndex: (state: State) => state.selected,
  selected: (state: State) =>
    state.selected !== null ? get(state, ['chapters', state.selected], null) : null,
}
