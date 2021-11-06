import { curry } from 'lodash'

const addQuery = (url: string, query: { [key: string]: any } = {}) => {
  const params = Object.keys(query)
    .map((k) => encodeURIComponent(k) + '=' + encodeURIComponent(query[k]))
    .join('&')

  return (url += (url.indexOf('?') === -1 ? '?' : '&') + params)
}

const defaultHeaders = (nonce: string, headers: { [key: string]: string } = {}) => ({
  'Content-Type': 'application/json',
  'X-WP-Nonce': nonce,
  ...headers,
})

const readApi = curry(
  (
    {
      nonce,
      method,
      urlProcessor,
    }: { nonce: string; method: 'GET' | 'DELETE'; urlProcessor?: (url: string) => string },
    url: string,
    { headers, query }: { headers?: { [key: string]: string }; query?: { [key: string]: string } }
  ) =>
    fetch(addQuery(urlProcessor ? urlProcessor(url) : url, query), {
      method,
      headers: defaultHeaders(nonce, headers),
    }).then(response => response.json())
)

const createApi = curry(
  (
    {
      nonce,
      method,
      urlProcessor,
    }: { nonce: string; method: 'POST' | 'PUT'; urlProcessor?: (url: string) => string },
    url: string,
    data: any,
    { headers, query }: { headers?: { [key: string]: string }; query?: { [key: string]: string } }
  ) =>
    fetch(addQuery(urlProcessor ? urlProcessor(url) : url, query), {
      method,
      headers: defaultHeaders(nonce, headers),
      body: JSON.stringify(data),
    }).then(response => response.json())
)

export const podlove = curry(
  ({ base, version, nonce }: { base: string; version: string; nonce: string }) => ({
    get: readApi({
      nonce,
      method: 'GET',
      urlProcessor: (endpoint) => `${base}/${version}/${endpoint}`,
    }),

    delete: readApi({
      nonce,
      method: 'DELETE',
      urlProcessor: (endpoint) => `${base}/${version}/${endpoint}`,
    }),

    post: createApi({
      nonce,
      method: 'POST',
      urlProcessor: (endpoint) => `${base}/${version}/${endpoint}`,
    }),

    put: createApi({
      nonce,
      method: 'PUT',
      urlProcessor: (endpoint) => `${base}/${version}/${endpoint}`,
    }),
  })
)

export const restClient = curry(
  ({ nonce }: { nonce: string }) => ({
    get: readApi({
      nonce,
      method: 'GET',
      urlProcessor: (endpoint) => `${globalThis.apiPrefix}${endpoint}`,
    }),

    delete: readApi({
      nonce,
      method: 'DELETE',
      urlProcessor: (endpoint) => `${globalThis.apiPrefix}${endpoint}`,
    }),

    post: createApi({
      nonce,
      method: 'POST',
      urlProcessor: (endpoint) => `${globalThis.apiPrefix}${endpoint}`,
    }),

    put: createApi({
      nonce,
      method: 'PUT',
      urlProcessor: (endpoint) => `${globalThis.apiPrefix}${endpoint}`,
    }),
  })
)
