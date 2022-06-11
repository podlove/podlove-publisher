import { createApp } from 'vue'
import { provideStore } from 'redux-vuex'
import { createI18n } from 'vue-i18n'

import { store } from '@store'
import modules from './modules'
import { init } from './store/lifecycle.store'
import { messages } from './translations'
import './style.css'

window.addEventListener('load', () => {
  document.querySelectorAll('[data-client="podlove"]:not([data-loaded="true"])').forEach((elem) => {
    elem.setAttribute('data-loaded', 'true')

    const app = createApp({
      components: {
        ...modules,
      },
    })

    const i18n = createI18n({
      locale: navigator.language,
      fallbackLocale: 'en',
      messages
    })

    provideStore({ store, app })

    app.use(i18n)
    app.mount(elem)
  });
});

(globalThis as any).initPodloveUI = (data: any) => {
  store.dispatch(init(data))
}
