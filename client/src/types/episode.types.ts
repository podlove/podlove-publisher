export interface PodloveEpisode {
  slug: string
  slug_frozen: boolean
  number: string
  title: string
  subtitle: string
  summary: string
  poster: string
  auphonic_chapter_timing_maps?: Record<
    string,
    {
      source_starts_ms: number[]
      output_starts_ms: number[]
    }
  >
}

export interface PodloveEpisodeContribution {
  id: number
  contributor_id: number
  role_id: number
  group_id: number
  position: number
  comment: string
}
