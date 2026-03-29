import { createApp } from 'vue'
import { provideStore } from 'redux-vuex'
import { store } from './plus/store'

import { init } from './store/lifecycle.store'
import translationPlugin from './plugins/translations'
import PodlovePlusFeatures from './modules/plus_features'
import PodlovePlusToken from './modules/plus_token'

import './style.css'

window.addEventListener('load', () => {
  document.querySelectorAll('[data-client="podlove"]:not([data-loaded="true"])').forEach((elem) => {
    elem.setAttribute('data-loaded', 'true')

    const app = createApp({
      components: {
        PodlovePlusFeatures,
        PodlovePlusToken,
      }
    })

    provideStore({ store, app })

    app.use(translationPlugin)
    app.mount(elem)
  })
})

;(globalThis as any).initPodloveUI = (data: any) => {
  store.dispatch(init(data))
}
