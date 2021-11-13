import { curry } from 'lodash'

const addQuery = (url: string, query: { [key: string]: any } = {}) => {
  const params = Object.keys(query)
    .map((k) => encodeURIComponent(k) + '=' + encodeURIComponent(query[k]))
    .join('&')

  return (url += (url.indexOf('?') === -1 ? '?' : '&') + params)
}

const defaultHeaders = (
  { nonce, auth }: { nonce: string; auth: string },
  headers: { [key: string]: string } = {}
) => ({
  'Content-Type': 'application/json',
  Accept: 'application/json',
  ...(auth ? { Authorization: `Basic ${auth}` } : {}),
  ...(nonce ? { 'X-WP-Nonce': nonce } : {}),
  ...headers,
})

const readApi =
  ({
    nonce,
    auth,
    method,
    urlProcessor,
  }: {
    nonce?: string
    auth?: string
    method: 'GET' | 'DELETE'
    urlProcessor?: (url: string) => string
  }) =>
  (
    url: string,
    { headers, query }: { headers?: { [key: string]: string }; query?: { [key: string]: string } } = {}
  ) =>
    fetch(addQuery(urlProcessor ? urlProcessor(url) : url, query), {
      method,
      headers: defaultHeaders({ nonce, auth }, headers),
    }).then((response) => response.json())

const createApi =
  (
    {
      nonce,
      auth,
      method,
      urlProcessor,
    }: {
      nonce?: string
      auth?: string
      method: 'POST' | 'PUT'
      urlProcessor?: (url: string) => string
    }) => (
    url: string,
    data: any,
    { headers, query }: { headers?: { [key: string]: string }; query?: { [key: string]: string } } = {}
  ) =>
    fetch(addQuery(urlProcessor ? urlProcessor(url) : url, query), {
      method,
      headers: defaultHeaders({ nonce, auth }, headers),
      body: JSON.stringify(data),
    }).then((response) => response.json())

export const podlove = curry(
  ({
    base,
    version,
    nonce,
    auth,
  }: {
    base: string
    version: string
    nonce?: string
    auth?: string
  }) => ({
    get: readApi({
      nonce,
      auth,
      method: 'GET',
      urlProcessor: (endpoint) => `${base}/${version}/${endpoint}`,
    }),

    delete: readApi({
      nonce,
      auth,
      method: 'DELETE',
      urlProcessor: (endpoint) => `${base}/${version}/${endpoint}`,
    }),

    post: createApi({
      nonce,
      auth,
      method: 'POST',
      urlProcessor: (endpoint) => `${base}/${version}/${endpoint}`,
    }),

    put: createApi({
      nonce,
      auth,
      method: 'PUT',
      urlProcessor: (endpoint) => `${base}/${version}/${endpoint}`,
    }),
  })
)

export const restClient = curry(({ nonce, auth }: { nonce?: string; auth?: string }) => ({
  get: readApi({
    nonce,
    auth,
    method: 'GET',
    urlProcessor: (endpoint) => `${globalThis.apiPrefix}${endpoint}`,
  }),

  delete: readApi({
    nonce,
    auth,
    method: 'DELETE',
    urlProcessor: (endpoint) => `${globalThis.apiPrefix}${endpoint}`,
  }),

  post: createApi({
    nonce,
    auth,
    method: 'POST',
    urlProcessor: (endpoint) => `${globalThis.apiPrefix}${endpoint}`,
  }),

  put: createApi({
    nonce,
    auth,
    method: 'PUT',
    urlProcessor: (endpoint) => `${globalThis.apiPrefix}${endpoint}`,
  }),
}))
