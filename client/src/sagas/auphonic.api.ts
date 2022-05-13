import { select } from 'redux-saga/effects'
import { auphonic } from '../lib/auphonic.api'
import { selectors, store } from '@store'
import { notify } from '@store/notification.store'

export function* createApi() {
  const base: string = 'https://auphonic.com/api'
  const bearer: string = yield select(selectors.auphonic.token)

  const errorHandler = function (response: any) {
    store.dispatch(notify({ type: 'error', message: `Auphonic: ${response.error_message}` }))
  }

  return auphonic({ base, bearer, errorHandler })
}
