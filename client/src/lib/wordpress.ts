import { get } from 'lodash'

export const store = get(window, ['wp', 'data'], null)
export const media = get(window, ['wp', 'media'], null)
export const postTitle = (cb: (title: string) => any) => document.getElementById('title')?.addEventListener('change', event => cb(get(event, ['target', 'value'])))
