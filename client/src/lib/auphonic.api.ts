import { curry } from 'lodash'
import { addQuery, responseParser, ApiOptions } from './api'

const defaultHeaders = (
  { bearer }: { bearer?: string },
  headers: { [key: string]: string } = {}
) => ({
  'Content-Type': 'application/json',
  Accept: 'application/json',
  ...(bearer ? { Authorization: `Bearer ${bearer}` } : {}),
  ...headers,
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

export interface AuphonicApiClient {
  get: (url: string, options?: ApiOptions) => Promise<{ result: any; error: any }>
}

export const auphonic = curry(
  ({ base, bearer, errorHandler }: { base: string; bearer?: string; errorHandler: Function }) => ({
    get: readApi({
      bearer,
      method: 'GET',
      errorHandler,
      urlProcessor: (endpoint) => `${base}/${endpoint}`,
    }),
  })
)
