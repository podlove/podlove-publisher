import { get, pick } from 'lodash'
import { handleActions } from 'redux-actions'
import { createAction } from 'redux-actions'
import Timestamp from '@lib/timestamp'
import * as lifecycle from './lifecycle.store'
import { PodloveEpisodeContribution } from '../types/episode.types'
import { PodloveContributor } from '../types/contributors.types'
import { arrayMove } from '@lib/array'

export const INIT = 'podlove/publisher/episode/INIT'
export const UPDATE = 'podlove/publisher/episode/UPDATE'
export const QUICKSAVE = 'podlove/publisher/episode/QUICKSAVE'
export const SAVED = 'podlove/publisher/episode/SAVED'
export const SLUG_CHANGED = 'podlove/publisher/episode/SLUG_CHANGED'
export const SET = 'podlove/publisher/episode/SET'
export const SET_POSTER = 'podlove/publisher/episode/SET_POSTER'
export const SELECT_POSTER = 'podlove/publisher/episode/SELECT_POSTER'
export const MOVE_CONTRIBUTION_UP = 'podlove/publisher/episode/MOVE_CONTRIBUTION_UP'
export const MOVE_CONTRIBUTION_DOWN = 'podlove/publisher/episode/MOVE_CONTRIBUTION_DOWN'
export const DELETE_CONTRIBUTION = 'podlove/publisher/episode/DELETE_CONTRIBUTION'
export const UPDATE_CONTRIBUTION = 'podlove/publisher/episode/UPDATE_CONTRIBUTION'
export const ADD_CONTRIBUTION = 'podlove/publisher/episode/ADD_CONTRIBUTION'
export const CREATE_CONTRIBUTION = 'podlove/publisher/episode/CREATE_CONTRIBUTION'

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
  auphonic_production_id: 'string' | null
  auphonic_webhook_config: object | null
  soundbite_start: number | null
  soundbite_duration: number | null
  soundbite_title: string | null
  license_name: string | null
  license_url: string | null
  contributions: PodloveEpisodeContribution[]
  show: string | null
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
  auphonic_production_id: null,
  auphonic_webhook_config: null,
  soundbite_start: null,
  soundbite_duration: null,
  soundbite_title: null,
  contributions: [],
  license_name: null,
  license_url: null,
  show: null,
}

export const update = createAction<{ prop: string; value: any }>(UPDATE)
export const quicksave = createAction<void>(QUICKSAVE)
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
  contributions?: object
  auphonic_production_id?: string
  auphonic_webhook_config?: object
  soundbite_start?: string
  soundbite_duration?: string
  soundbite_title?: string
  license_name?: string
  license_url?: string
  show?: string
}>(SET)
export const moveContributionUp = createAction<PodloveEpisodeContribution>(MOVE_CONTRIBUTION_UP)
export const moveContributionDown = createAction<PodloveEpisodeContribution>(MOVE_CONTRIBUTION_DOWN)
export const deleteContribution = createAction<PodloveEpisodeContribution>(DELETE_CONTRIBUTION)
export const updateContribution = createAction<PodloveEpisodeContribution>(UPDATE_CONTRIBUTION)
export const addContribution = createAction<Partial<PodloveContributor>>(ADD_CONTRIBUTION)
export const createContribution = createAction<string>(CREATE_CONTRIBUTION)
export const saved = createAction<object>(SAVED)
export const slugChanged = createAction<void>(SLUG_CHANGED)

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
      const simple = [
        'title',
        'subtitle',
        'summary',
        'duration',
        'slug',
        'auphonic_webhook_config',
        'soundbite_start',
        'soundbite_duration',
        'soundbite_title',
        'license_name',
        'license_url',
        'show',
        'number',
        'episode_poster',
      ]

      if (simple.includes(prop)) {
        return { ...state, [prop]: value }
      } else {
        console.debug('todo', prop, value)
        return { ...state }
      }
    },
    [SET]: (state: State, action: typeof update): State => ({
      ...state,
      slug: get(action, ['payload', 'slug'], state.slug),
      number: get(action, ['payload', 'number'], state.number),
      duration: get(action, ['payload', 'duration'], state.duration),
      title: get(action, ['payload', 'title_clean'], state.title),
      subtitle: get(action, ['payload', 'subtitle'], state.subtitle),
      summary: get(action, ['payload', 'summary'], state.summary),
      type: get(action, ['payload', 'type'], state.type),
      episode_poster: get(action, ['payload', 'episode_poster'], state.episode_poster),
      poster: get(action, ['payload', 'poster'], state.poster),
      mnemonic: get(action, ['payload', 'mnemonic'], state.mnemonic),
      explicit: get(action, ['payload', 'explicit'], state.explicit),
      auphonic_production_id: get(
        action,
        ['payload', 'auphonic_production_id'],
        state.auphonic_production_id
      ),
      auphonic_webhook_config: get(
        action,
        ['payload', 'auphonic_webhook_config'],
        state.auphonic_webhook_config
      ),
      soundbite_start: get(action, ['payload', 'soundbite_start'], state.soundbite_start),
      soundbite_duration: get(action, ['payload', 'soundbite_duration'], state.soundbite_duration),
      soundbite_title: get(action, ['payload', 'soundbite_title'], state.soundbite_title),
      license_name: get(action, ['payload', 'license_name'], state.license_name),
      license_url: get(action, ['payload', 'license_url'], state.license_url),
      contributions: get(action, ['payload', 'contributions'], state.contributions),
      show: get(action, ['payload', 'show'], state.show),
    }),
    [MOVE_CONTRIBUTION_UP]: (state: State, action: typeof moveContributionUp): State => {
      const index = state.contributions.findIndex(
        (contribution) => contribution.position === get(action, ['payload', 'position'])
      )

      if (index < 1) {
        return state
      }

      return {
        ...state,
        contributions: arrayMove(state.contributions, index, index - 1).map(
          (contribution, position) => ({ ...contribution, position })
        ),
      }
    },
    [MOVE_CONTRIBUTION_DOWN]: (state: State, action: typeof moveContributionDown): State => {
      const index = state.contributions.findIndex(
        (contribution) => contribution.position === get(action, ['payload', 'position'])
      )

      if (index > state.contributions.length) {
        return state
      }

      return {
        ...state,
        contributions: arrayMove(state.contributions, index, index + 1).map(
          (contribution, position) => ({ ...contribution, position })
        ),
      }
    },
    [DELETE_CONTRIBUTION]: (state: State, action: typeof deleteContribution): State => ({
      ...state,
      contributions: state.contributions
        .filter(({ position }) => get(action, ['payload', 'position']) !== position)
        .map((contribution, position) => ({ ...contribution, position })),
    }),
    [UPDATE_CONTRIBUTION]: (state: State, action: typeof updateContribution): State => ({
      ...state,
      contributions: state.contributions.map((contribution) => {
        if (contribution.contributor_id !== get(action, ['payload', 'contributor_id'])) {
          return contribution
        }

        return pick(get(action, ['payload'], {}), [
          'id',
          'contributor_id',
          'role_id',
          'group_id',
          'position',
          'comment',
        ])
      }),
    }),
    [ADD_CONTRIBUTION]: (state: State, action: typeof addContribution) => ({
      ...state,
      contributions: [
        ...state.contributions,
        {
          id: null,
          contributor_id: get(action, ['payload', 'id'], null),
          role_id: null,
          group_id: null,
          position: state.contributions.length,
          comment: null,
        },
      ],
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
  auphonicProductionId: (state: State) => state.auphonic_production_id,
  auphonicWebhookConfig: (state: State) => state.auphonic_webhook_config,
  soundbite_start: (state: State) => state.soundbite_start,
  soundbite_duration: (state: State) => state.soundbite_duration,
  soundbite_title: (state: State) => state.soundbite_title,
  license_name: (state: State) => state.license_name,
  license_url: (state: State) => state.license_url,
  contributions: (state: State) => state.contributions,
  currentShow: (state: State) => state.show,
}
