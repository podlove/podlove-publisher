import { createApiFactory } from '../../lib/createApiFactory'
import { store } from '../store'
import { waitFor } from '../../sagas/helper'
import * as lifecycle from '../../store/lifecycle.store'
import * as runtime from '../../store/runtime.store'

export const createApi = createApiFactory({
  selectBootstrapped: (state: any) => lifecycle.selectors.bootstrapped(state.lifecycle),
  selectBase: (state: any) => runtime.selectors.base(state.runtime),
  selectNonce: (state: any) => runtime.selectors.nonce(state.runtime),
  selectAuth: (state: any) => runtime.selectors.auth(state.runtime),
  selectBearer: (state: any) => runtime.selectors.bearer(state.runtime),
  waitFor,
  onError: (message) => {
    // No notification UI exists on the PLUS-only bundle, so fall back to the console.
    store.dispatch({ type: 'podlove/publisher/plus/API_ERROR', payload: message })
    console.error(message)
  },
})
