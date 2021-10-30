import { provideStore } from 'redux-vuex'
import { createApp } from 'vue'

import { store } from '@store'
import episode from './modules'
import { init } from './store/lifecycle.store'
import './tailwind.css';

document.querySelectorAll('[data-client="podlove"]').forEach(elem => {
  const app = createApp({
    components: {
      ...episode
    }
  })

  provideStore({ store, app })
  app.mount(elem)
})

store.dispatch(init(globalThis.PODLOVE_DATA))
