import { curry } from 'lodash'
import axios from 'axios'
import { addQuery, responseParser, ApiOptions } from './api'

// TODO: replace fetch with axios

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

const deleteApi =
  ({
    errorHandler,
    bearer,
    method,
    urlProcessor,
  }: {
    errorHandler?: Function
    bearer?: string
    method: 'DELETE'
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
    const formData = new FormData()

    // audio file upload
    if (data.file) {
      // track id for multitrack, 'input_file' for single track
      const id = data.track_id || 'input_file'
      formData.append(id, data.file)
    }

    // cover poster upload
    if (data.image) {
      formData.append('image', data.image)
    }

    return axios.post(addQuery(urlProcessor ? urlProcessor(url) : url, query), formData, {
      headers: {
        ...authHeaders({ bearer }),
      },
      onUploadProgress: (e) => console.log('progress', e),
    })
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
    delete: deleteApi({
      bearer,
      errorHandler,
      method: 'DELETE',
      urlProcessor: (endpoint) => `${base}/${endpoint}`,
    }),
    upload: uploadApi({
      bearer,
      errorHandler,
      urlProcessor: (endpoint) => `${base}/${endpoint}`,
    }),
  })
)
