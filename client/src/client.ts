import { createApp } from 'vue'
import { provideStore } from 'redux-vuex'
import { store } from '@store'

import modules from './modules'
import { init } from './store/lifecycle.store'
import translationPlugin from './plugins/translations'

import './style.css'

window.addEventListener('load', () => {
  document.querySelectorAll('[data-client="podlove"]:not([data-loaded="true"])').forEach((elem) => {
    elem.setAttribute('data-loaded', 'true')

    const app = createApp({
      components: {
        ...modules,
      }
    })

    provideStore({ store, app })

    app.use(translationPlugin, { domain: 'podlove-podcasting-plugin-for-wordpress' })
    app.mount(elem)
  });
});

(globalThis as any).initPodloveUI = (data: any) => {
  store.dispatch(init(data))
}
