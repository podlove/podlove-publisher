import { get } from 'lodash'
import { App } from 'vue'

declare module '@vue/runtime-core' {
  interface ComponentCustomProperties {
    __: (translation: string, domain: string) => string;
    _n: (single: string, plural: string, number: number, domain: string) => string;
    sprintf: (format: string, ...args: any[]) => string;
  }
}

const translate = get(window, ['wp', 'i18n', '__'], (translation: string) => translation)

export const __ = translate;
export const _n = get(window, ['wp', 'i18n', '_n'], (single: string, plural: string, number: number) => number === 1 ? single : plural);
export const sprintf = get(window, ['wp', 'i18n', 'sprintf'], (format: string, ...args: any[]) => {
  let index = 0
  return format.replace(/%(?:(\d+)\$)?[sd]/g, (_match: string, position: string) => String(args[position ? Number(position) - 1 : index++]))
});

export default {
  install(app: App) {
    app.config.globalProperties['_n'] = _n
    app.config.globalProperties['sprintf'] = sprintf
    app.config.globalProperties['__'] = (translation: string, domain: string) => translate(translation, domain)
  },
}
