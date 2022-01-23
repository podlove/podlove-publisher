import { provideStore } from 'redux-vuex'
import { createApp } from 'vue'

import { store } from '@store'
import modules from './modules'
import { init } from './store/lifecycle.store'
import './style.css'

document.querySelectorAll('[data-client="podlove"]:not([data-loaded="true"])').forEach((elem) => {
  elem.setAttribute('data-loaded', 'true')

  const app = createApp({
    components: {
      ...modules,
    },
  })

  provideStore({ store, app })
  app.mount(elem)
});

(globalThis as any).initPodloveUI = (data: any) => {
  store.dispatch(init(data))
}
