export interface PodloveContributor {
  id: string
  avatar: string
  avatar_url: string
  count: string
  department: string
  gender: string
  jobtitle: string
  mail: string
  realname: string
  nickname: string
  organisation: string
  slug: string
}

export interface PodloveRole {
  id: number
  slug: string
  title: string
}

export interface PodloveGroup {
  id: number
  slug: string
  title: string
}
