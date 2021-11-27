import { provideStore } from 'redux-vuex'
import { createApp } from 'vue'

import { store } from '@store'
<<<<<<< HEAD
import modules from './modules'
import { init } from './store/lifecycle.store'
import './tailwind.css';


document.querySelectorAll('[data-client="podlove"]:not([data-loaded="true"])').forEach(elem => {
  elem.setAttribute('data-loaded', 'true');

  const app = createApp({
    components: {
      ...modules
=======
import episode from './modules/episode'
import { init } from './store/lifecycle.store'
import './tailwind.css';

document.querySelectorAll('[data-client="podlove"]').forEach(elem => {
  const app = createApp({
    components: {
      ...episode
>>>>>>> 6ca060a4744249c97d016dd3c3b420a4285881e3
    }
  })

  provideStore({ store, app })
  app.mount(elem)
})

store.dispatch(init(globalThis.PODLOVE_DATA))
