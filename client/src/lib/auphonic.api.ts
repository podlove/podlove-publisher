import { curry } from 'lodash'
import { addQuery, responseParser, ApiOptions } from './api'

const defaultHeaders = (
  { bearer }: { bearer?: string },
  headers: { [key: string]: string } = {}
) => ({
  'Content-Type': 'application/json',
  Accept: 'application/json',
  ...authHeaders({ bearer }),
  ...headers,
})

const authHeaders = ({ bearer }: { bearer?: string }) => ({
  ...(bearer ? { Authorization: `Bearer ${bearer}` } : {}),
})

const readApi =
  ({
    errorHandler,
    bearer,
    method,
    urlProcessor,
  }: {
    errorHandler?: Function
    bearer?: string
    method: 'GET' | 'DELETE'
    urlProcessor?: (url: string) => string
  }) =>
  (url: string, { headers, query }: ApiOptions = {}) =>
    fetch(addQuery(urlProcessor ? urlProcessor(url) : url, query), {
      method,
      headers: defaultHeaders({ bearer }, headers),
    }).then(responseParser(errorHandler))

const createApi =
  ({
    errorHandler,
    bearer,
    method,
    urlProcessor,
  }: {
    errorHandler?: Function
    bearer?: string
    method: 'POST' | 'PUT'
    urlProcessor?: (url: string) => string
  }) =>
  (url: string, data: any, { headers, query }: ApiOptions = {}) => {
    return fetch(addQuery(urlProcessor ? urlProcessor(url) : url, query), {
      method,
      headers: defaultHeaders({ bearer }, headers),
      body: JSON.stringify(data),
    }).then(responseParser(errorHandler))
  }

const uploadApi =
  ({
    errorHandler,
    bearer,
    urlProcessor,
  }: {
    errorHandler?: Function
    bearer?: string
    urlProcessor?: (url: string) => string
  }) =>
  (url: string, data: any, { query }: ApiOptions = {}) => {
    // TODO: this works but without progress reporting, apparently not possible with fetch
    // maybe change back to XMLHttpRequest
    const formData = new FormData()
    formData.append('input_file', data)

    return fetch(addQuery(urlProcessor ? urlProcessor(url) : url, query), {
      mode: 'cors',
      method: 'POST',
      headers: authHeaders({ bearer }),
      body: formData,
    }).then(responseParser(errorHandler))
  }

export interface AuphonicApiClient {
  get: (url: string, options?: ApiOptions) => Promise<{ result: any; error: any }>
  post: (url: string, data: any, options?: ApiOptions) => Promise<{ result: any; error: any }>
  upload: (url: string, data: any, options?: ApiOptions) => Promise<{ result: any; error: any }>
}

export const auphonic = curry(
  ({ base, bearer, errorHandler }: { base: string; bearer?: string; errorHandler: Function }) => ({
    get: readApi({
      bearer,
      method: 'GET',
      errorHandler,
      urlProcessor: (endpoint) => `${base}/${endpoint}`,
    }),
    post: createApi({
      bearer,
      errorHandler,
      method: 'POST',
      urlProcessor: (endpoint) => `${base}/${endpoint}`,
    }),
    upload: uploadApi({
      bearer,
      errorHandler,
      method: 'POST',
      urlProcessor: (endpoint) => `${base}/${endpoint}`,
    }),
  })
)
