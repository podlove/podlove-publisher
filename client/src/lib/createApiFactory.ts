import { call, select } from 'redux-saga/effects'
import { podlove } from './api'

type CreateApiOptions = {
  selectBootstrapped: (state: any) => boolean
  selectBase: (state: any) => string | null
  selectNonce: (state: any) => string | null
  selectAuth: (state: any) => string | null
  selectBearer: (state: any) => string | null
  waitFor: (selector: any) => Generator<any, void, any>
  onError: (message: string, errorData: any) => void
}

export const createApiFactory = ({
  selectBootstrapped,
  selectBase,
  selectNonce,
  selectAuth,
  selectBearer,
  waitFor,
  onError,
}: CreateApiOptions) => {
  return function* createApi() {
    yield call(waitFor, selectBootstrapped)

    const base: string | null = yield select(selectBase)
    const nonce: string | null = yield select(selectNonce)
    const auth: string | null = yield select(selectAuth)
    const bearer: string | null = yield select(selectBearer)

    const errorHandler = function (errorData: any) {
      let message = 'An error occurred'

      if (typeof errorData === 'string') {
        message = errorData
      } else if (errorData && typeof errorData === 'object') {
        if (errorData.code && errorData.message) {
          message = `${errorData.code}: ${errorData.message}`
        } else {
          message = errorData.message || errorData.code || 'An error occurred'
        }
      }

      onError(message, errorData)
    }

    return podlove({ base: base || '', version: 'v2', nonce: nonce || undefined, auth: auth || undefined, bearer: bearer || undefined, errorHandler })
  }
}
