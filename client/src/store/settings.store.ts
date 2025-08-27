import { get } from 'lodash'
import { handleActions } from 'redux-actions'
import { init, INIT } from './lifecycle.store'

type TrackingMode = 'ptm_analytics'
type TrackingWindow = 'daily'

export interface State {
  plus: {
    storage_enabled: boolean | null
  }
  metadata: {
    enable_episode_explicit: boolean | null
    enable_episode_license: boolean | null
    enable_episode_recording_date: boolean | null
  }
  tracking: {
    mode: TrackingMode | null
    window: TrackingWindow | null
  }
  website: {
    blog_title_template: string | null
    custom_episode_slug: string | null
    enable_generated_blog_post_title: boolean | null
    episode_archive: boolean | null
    episode_archive_slug: string | null
    episode_number_padding: string | null
    feeds_skip_redirect: boolean | null
    hide_wp_feed_discovery: boolean | null
    landing_page: string | null
    merge_episodes: boolean | null
    ssl_verify_peer: boolean | null
    url_template: string | null
    use_post_permastruct: boolean | null
  }
  assets: {
    image: null | 'podcast-cover' | 'post-thumbnail' | 'manual'
    chapter: null | 'none' | 'manual'
    transcript: null | 'manual'
  }
  media: {
    base_uri: null | string
  }
  modules: string[]
}

export const initialState: State = {
  plus: {
    storage_enabled: null,
  },
  metadata: {
    enable_episode_explicit: null,
    enable_episode_license: null,
    enable_episode_recording_date: null,
  },
  tracking: {
    mode: null,
    window: null,
  },
  website: {
    blog_title_template: null,
    custom_episode_slug: null,
    enable_generated_blog_post_title: null,
    episode_archive: null,
    episode_archive_slug: null,
    episode_number_padding: null,
    feeds_skip_redirect: null,
    hide_wp_feed_discovery: null,
    landing_page: null,
    merge_episodes: null,
    ssl_verify_peer: null,
    url_template: null,
    use_post_permastruct: null,
  },
  assets: {
    image: null,
    chapter: null,
    transcript: null,
  },
  media: {
    base_uri: null,
  },
  modules: [],
}

const normalizeAssignmentImage = (
  input: string
): null | 'podcast-cover' | 'post-thumbnail' | 'manual' => {
  switch (input) {
    case '0':
      return 'podcast-cover'
    case 'post-thumbnail':
      return 'post-thumbnail'
    case 'manual':
      return 'manual'
    default:
      return null
  }
}

const normalizeAssignmentChapter = (input: string): null | 'none' | 'manual' => {
  switch (input) {
    case '0':
      return 'none'
    case 'manual':
      return 'manual'
    default:
      return null
  }
}

export const reducer = handleActions(
  {
    [INIT]: (state: State, action: typeof init): State => ({
      ...state,
      plus: {
        storage_enabled: get(action, ['payload', 'plus', 'storage_enabled'], null) === true,
      },
      metadata: {
        enable_episode_explicit:
          get(
            action,
            ['payload', 'expert_settings', 'metadata', 'enable_episode_explicit'],
            null
          ) === '1',
        enable_episode_license:
          get(
            action,
            ['payload', 'expert_settings', 'metadata', 'enable_episode_license'],
            null
          ) === '1',
        enable_episode_recording_date:
          get(
            action,
            ['payload', 'expert_settings', 'metadata', 'enable_episode_recording_date'],
            null
          ) === '1',
      },
      tracking: {
        mode: get(action, ['payload', 'expert_settings', 'tracking', 'mode'], null),
        window: get(action, ['payload', 'expert_settings', 'tracking', 'mode'], null),
      },
      media: {
        base_uri: get(action, ['payload', 'media', 'base_uri'], null),
      },
      website: {
        blog_title_template: get(
          action,
          ['payload', 'expert_settings', 'website', 'blog_title_template'],
          null
        ),
        custom_episode_slug: get(
          action,
          ['payload', 'expert_settings', 'website', 'custom_episode_slug'],
          null
        ),
        enable_generated_blog_post_title:
          get(
            action,
            ['payload', 'expert_settings', 'website', 'enable_generated_blog_post_title'],
            null
          ) === 'on',
        episode_archive:
          get(action, ['payload', 'expert_settings', 'website', 'episode_archive'], null) === 'on',
        episode_archive_slug: get(
          action,
          ['payload', 'expert_settings', 'website', 'episode_archive_slug'],
          null
        ),
        episode_number_padding: get(
          action,
          ['payload', 'expert_settings', 'website', 'episode_number_padding'],
          null
        ),
        feeds_skip_redirect:
          get(action, ['payload', 'expert_settings', 'website', 'feeds_skip_redirect'], null) ===
          'on',
        hide_wp_feed_discovery:
          get(action, ['payload', 'expert_settings', 'website', 'hide_wp_feed_discovery'], null) ===
          'on',
        landing_page: get(action, ['payload', 'expert_settings', 'website', 'landing_page'], null),
        merge_episodes:
          get(action, ['payload', 'expert_settings', 'website', 'merge_episodes'], null) === 'on',
        ssl_verify_peer:
          get(action, ['payload', 'expert_settings', 'website', 'ssl_verify_peer'], null) === 'on',
        url_template: get(action, ['payload', 'expert_settings', 'website', 'url_template'], null),
        use_post_permastruct:
          get(action, ['payload', 'expert_settings', 'website', 'use_post_permastruct'], null) ===
          'on',
      },
      assets: {
        image: normalizeAssignmentImage(get(action, ['payload', 'assignments', 'image'], null)),
        chapter: normalizeAssignmentChapter(
          get(action, ['payload', 'assignments', 'chapter'], null)
        ),
        transcript: get(action, ['payload', 'assignments', 'transcript'], null),
      },
      modules: get(action, ['payload', 'modules']),
    }),
  },
  initialState
)

export const selectors = {
  autoGenerateEpisodeTitle: (state: State) => state.website.enable_generated_blog_post_title,
  blogTitleTemplate: (state: State) => state.website.blog_title_template,
  episodeNumberPadding: (state: State) => state.website.episode_number_padding,
  imageAsset: (state: State) => state.assets.image,
  enableEpisodeExplicit: (state: State) => state.metadata.enable_episode_explicit,
  enablePlusStorage: (state: State) => state.plus.storage_enabled,
  mediaFileBaseUri: (state: State) => state.media.base_uri,
  modules: (state: State) => state.modules,
}
