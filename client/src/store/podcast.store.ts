import { get } from 'lodash'
import { handleActions } from 'redux-actions'
import { createAction } from 'redux-actions'

export const INIT = 'podlove/publisher/podcast/INIT'
export const SET = 'podlove/publisher/podcast/SET'
export const SAVED = 'podlove/publisher/podcasr/SAVED'
export const UPDATE = 'podlove/publisher/podcast/UPDATE'


export type State = {
  title: string | null
  subtitle: string | null
  summary: string | null
  mnemonic: string | null
  itunes_type: string | null
  author_name: string | null
  poster: string | null
  link: string | null
  license_name: string | null
  license_url: string | null
}

export const initialState: State = {
  title: null,
  subtitle: null,
  summary: null,
  mnemonic: null,
  itunes_type: null,
  author_name: null,
  poster: null,
  link: null,
  license_name: null,
  license_url: null
}

export const init = createAction<void>(INIT)
export const set = createAction<Partial<State>>(SET)
export const saved = createAction<object>(SAVED)
export const update = createAction<{ prop: string; value: any }>(UPDATE)

export const reducer = handleActions(
  {
    [SET]: (state: State, action: { payload: Partial<State> }): State => ({
      title: get(action , ['payload', 'title'], state.title),
      subtitle: get(action , ['payload', 'subtitle'], state.subtitle),
      summary: get(action , ['payload', 'summary'], state.summary),
      mnemonic: get(action , ['payload', 'mnemonic'], state.mnemonic),
      itunes_type: get(action , ['payload', 'itunes_type'], state.itunes_type),
      author_name: get(action , ['payload', 'author_name'], state.author_name),
      poster: get(action , ['payload', 'poster'], state.poster),
      link: get(action , ['payload', 'link'], state.link),
      license_name: get(action , ['payload', 'license_name'], state.license_name),
      license_url: get(action , ['payload', 'license_url'], state.license_url),
    }),
    [UPDATE]: (state: State, action: typeof update): State => {
      const prop = get(action, ['payload', 'prop'])
      const value = get(action, ['payload', 'value'], null)

      const simple = [
        'title',
        'subtitle',
        'summary',
        'author_name',
        'podcast_email',
        'funding_url',
        'funding_label',
        'license_name',
        'license_url',
      ]

      if (simple.includes(prop)) {
        return { ...state, [prop]: value }
      } else {
        console.debug('todo', prop, value)
        return { ...state }
      }
    },
  },
  initialState
)

export const selectors = {
  title: (state: State) => state.title,
  subtitle: (state: State) => state.subtitle,
  summary: (state: State) => state.summary,
  mnemonic: (state: State) => state.mnemonic,
  itunesType: (state: State) => state.itunes_type,
  author: (state: State) => state.author_name,
  poster: (state: State) => state.poster,
  link: (state: State) => state.link,
  license_name: (state: State) => state.license_name,
  license_url: (state: State) => state.license_url,
}
