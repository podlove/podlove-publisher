import { get } from 'lodash'

export const store = get(window, ['wp', 'data'], null)
export const media = get(window, ['wp', 'media'], null)
export const htmlEntities = get(window, ['wp', 'htmlEntities'], null)
export const postTitleInput: HTMLInputElement | null = document.querySelector('input[name="post_title"]')
export const postTitleListener = (cb: (title: string) => any) => postTitleInput?.addEventListener('change', event => cb(get(event, ['target', 'value'])))
