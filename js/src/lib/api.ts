import { curry } from 'lodash'

const addQuery = (url: string, query: { [key: string]: any } = {}) => {
  const params = Object.keys(query)
    .map((k) => encodeURIComponent(k) + '=' + encodeURIComponent(query[k]))
    .join('&')

  return (url += (url.indexOf('?') === -1 ? '?' : '&') + params)
}

const responseParser = async (response: Response) => {
  const result = await response.json();

  if (response.status >= 300) {
    return {
      error: result
    }
  }

  return {
    result
  }
}

const defaultHeaders = (
  { nonce, auth, bearer }: { nonce: string; auth: string, bearer: string },
  headers: { [key: string]: string } = {}
) => ({
  'Content-Type': 'application/json',
  Accept: 'application/json',
  ...(bearer ? { Authorization: `Bearer ${bearer}` } : {} ),
  ...(auth ? { Authorization: `Basic ${auth}` } : {}),
  ...(nonce ? { 'X-WP-Nonce': nonce } : {}),
  ...headers,
})

const readApi =
  ({
    nonce,
    auth,
    bearer,
    method,
    urlProcessor,
  }: {
    nonce?: string;
    auth?: string;
    bearer?: string;
    method: 'GET' | 'DELETE';
    urlProcessor?: (url: string) => string;
  }) =>
  (
    url: string,
    { headers, query }: { headers?: { [key: string]: string }; query?: { [key: string]: string } } = {}
  ) =>
    fetch(addQuery(urlProcessor ? urlProcessor(url) : url, query), {
      method,
      headers: defaultHeaders({ nonce, auth, bearer }, headers),
    }).then(responseParser)

const createApi =
  (
    {
      nonce,
      auth,
      bearer,
      method,
      urlProcessor,
    }: {
      nonce?: string;
      auth?: string;
      bearer?: string;
      method: 'POST' | 'PUT';
      urlProcessor?: (url: string) => string;
    }) => (
    url: string,
    data: any,
    { headers, query }: { headers?: { [key: string]: string }; query?: { [key: string]: string } } = {}
  ) =>
    fetch(addQuery(urlProcessor ? urlProcessor(url) : url, query), {
      method,
      headers: defaultHeaders({ nonce, auth, bearer }, headers),
      body: JSON.stringify(data),
    }).then((response) => response.json()).then((result) => ({ result })).catch(error => ({ error }))

export const podlove = curry(
  ({
    base,
    version,
    nonce,
    auth,
    bearer
  }: {
    base: string
    version: string
    nonce?: string
    auth?: string
    bearer?: string
  }) => ({
    get: readApi({
      nonce,
      auth,
      bearer,
      method: 'GET',
      urlProcessor: (endpoint) => `${base}/${version}/${endpoint}`,
    }),

    delete: readApi({
      nonce,
      auth,
      bearer,
      method: 'DELETE',
      urlProcessor: (endpoint) => `${base}/${version}/${endpoint}`,
    }),

    post: createApi({
      nonce,
      auth,
      bearer,
      method: 'POST',
      urlProcessor: (endpoint) => `${base}/${version}/${endpoint}`,
    }),

    put: createApi({
      nonce,
      auth,
      bearer,
      method: 'PUT',
      urlProcessor: (endpoint) => `${base}/${version}/${endpoint}`,
    }),
  })
)

export const restClient = curry(({ nonce, auth, bearer }: { nonce?: string; auth?: string; bearer?: string }) => ({
  get: readApi({
    nonce,
    auth,
    bearer,
    method: 'GET',
    urlProcessor: (endpoint) => `${globalThis.apiPrefix}${endpoint}`,
  }),

  delete: readApi({
    nonce,
    auth,
    bearer,
    method: 'DELETE',
    urlProcessor: (endpoint) => `${globalThis.apiPrefix}${endpoint}`,
  }),

  post: createApi({
    nonce,
    auth,
    bearer,
    method: 'POST',
    urlProcessor: (endpoint) => `${globalThis.apiPrefix}${endpoint}`,
  }),

  put: createApi({
    nonce,
    auth,
    bearer,
    method: 'PUT',
    urlProcessor: (endpoint) => `${globalThis.apiPrefix}${endpoint}`,
  }),
}))
