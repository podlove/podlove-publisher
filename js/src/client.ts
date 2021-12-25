import { provideStore } from 'redux-vuex'
import { createApp } from 'vue'

import { store } from '@store'
import modules from './modules'
import { init } from './store/lifecycle.store'
import './tailwind.css';


document.querySelectorAll('[data-client="podlove"]:not([data-loaded="true"])').forEach(elem => {
  elem.setAttribute('data-loaded', 'true');

  const app = createApp({
    components: {
      ...modules
    }
  })

  provideStore({ store, app })
  app.mount(elem)
})

store.dispatch(init((globalThis as any).PODLOVE_DATA))
