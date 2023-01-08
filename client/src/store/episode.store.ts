import { get } from 'lodash'
import { handleActions } from 'redux-actions'
import { createAction } from 'redux-actions'
import Timestamp from '@lib/timestamp'
import * as lifecycle from './lifecycle.store'

export const INIT = 'podlove/publisher/episode/INIT'
export const UPDATE = 'podlove/publisher/episode/UPDATE'
export const SET = 'podlove/publisher/episode/SET'
export const SET_POSTER = 'podlove/publisher/episode/SET_POSTER'
export const SELECT_POSTER = 'podlove/publisher/episode/SELECT_POSTER'

export type State = {
  id: string | null
  slug: string | null
  duration: number | null
  number: string | null
  title: string | null
  subtitle: string | null
  summary: string | null
  type: 'full' | 'trailer' | 'bonus' | null
  episode_poster: string | null
  poster: string | null
  mnemonic: string | null
  explicit: boolean | null
  soundbite_start: number | null
  soundbite_duration: number | null
  soundbite_title: string | null
  auphonicProductionId: 'string' | null
  auphonic_webhook_config: object | null
}

export const initialState: State = {
  id: null,
  slug: null,
  duration: null,
  number: null,
  subtitle: null,
  title: null,
  summary: null,
  type: null,
  episode_poster: null,
  poster: null,
  mnemonic: null,
  explicit: null,
  soundbite_start: null,
  soundbite_duration: null,
  soundbite_title: null,
  auphonicProductionId: null,
  auphonic_webhook_config: null,
}

export const update = createAction<{ prop: string; value: string | boolean }>(UPDATE)
export const init = createAction<void>(INIT)
export const selectPoster = createAction<void>(SELECT_POSTER)
export const set = createAction<{
  slug?: string
  number?: string
  duration?: string
  title?: string
  subtitle?: string
  summary?: string
  episode_poster?: string
  poster?: string
  mnemonic?: string
  explicit?: boolean
  soundbite_start?: string
  soundbite_duration?: string
  soundbite_title?: string
  auphonicProductionId?: string
  auphonic_webhook_config?: object
}>(SET)

export const reducer = handleActions(
  {
    [lifecycle.INIT]: (state: State, action: typeof lifecycle.init): State => ({
      ...state,
      id: get(action, ['payload', 'episode', 'id'], null),
      duration: Timestamp.fromString(get(action, ['payload', 'episode', 'duration'], null)).totalMs,
    }),
    [UPDATE]: (state: State, action: typeof update): State => {
      const prop = get(action, ['payload', 'prop'])
      const value = get(action, ['payload', 'value'], null)

      // FIXME: finish implementation once episode saga supports it
      const simple = ['title', 'subtitle', 'summary', 'duration', 'slug', 'soundbite_start', 'soundbite_duration', 'soundbite_title']
      const other = ['image']
      const todo = ['tags', 'license', 'license url']

      if (simple.includes(prop)) {
        return { ...state, [prop]: value }
      } else if (prop == 'image') {
        return { ...state, ['episode_poster']: value }
      } else {
        console.debug('todo', prop)
        return { ...state }
      }
    },
    [SET]: (state: State, action: typeof update): State => ({
      ...state,
      slug: get(action, ['payload', 'slug']),
      number: get(action, ['payload', 'number']),
      title: get(action, ['payload', 'title_clean']),
      duration: get(action, ['payload', 'duration']),
      subtitle: get(action, ['payload', 'subtitle']),
      summary: get(action, ['payload', 'summary']),
      type: get(action, ['payload', 'type']),
      episode_poster: get(action, ['payload', 'episode_poster']),
      poster: get(action, ['payload', 'poster']),
      mnemonic: get(action, ['payload', 'mnemonic']),
      explicit: get(action, ['payload', 'explicit']),
      soundbite_start: get(action, ['payload', 'soundbite_start']),
      soundbite_duration: get(action, ['payload', 'soundbite_duration']),
      soundbite_title: get(action, ['payload', 'soundbite_title']),
      auphonicProductionId: get(action, ['payload', 'auphonic_production_id']),
      auphonic_webhook_config: get(action, ['payload', 'auphonic_webhook_config']),
    }),
  },
  initialState
)

export const selectors = {
  id: (state: State) => state.id,
  slug: (state: State) => state.slug,
  duration: (state: State) => state.duration,
  number: (state: State) => state.number,
  title: (state: State) => state.title,
  subtitle: (state: State) => state.subtitle,
  summary: (state: State) => state.summary,
  type: (state: State) => state.type,
  poster: (state: State) => state.poster,
  episodePoster: (state: State) => state.episode_poster,
  mnemonic: (state: State) => state.mnemonic,
  explicit: (state: State) => state.explicit,
  soundbite_start: (state: State) => state.soundbite_start,
  soundbite_duration: (state: State) => state.soundbite_duration,
  soundbite_title: (state: State) => state.soundbite_title,
  auphonicProductionId: (state: State) => state.auphonicProductionId,
  auphonicWebhookConfig: (state: State) => state.auphonic_webhook_config,
}
