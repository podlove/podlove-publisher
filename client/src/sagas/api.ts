import { createApiFactory } from '../lib/createApiFactory'
import { selectors, store } from '@store'
import { notify } from '@store/notification.store'
import { waitFor } from './helper'

export const createApi = createApiFactory({
  selectBootstrapped: selectors.lifecycle.bootstrapped,
  selectBase: selectors.runtime.base,
  selectNonce: selectors.runtime.nonce,
  selectAuth: selectors.runtime.auth,
  selectBearer: selectors.runtime.bearer,
  waitFor,
  onError: (message) => {
    store.dispatch(notify({ type: 'error', message }))
  },
})
