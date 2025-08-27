export interface PodloveEpisode {
  slug: string
  slug_frozen: boolean
  number: string
  title: string
  subtitle: string
  summary: string
  poster: string
}

export interface PodloveEpisodeContribution {
  id: number
  contributor_id: number
  role_id: number
  group_id: number
  position: number
  comment: string
}
