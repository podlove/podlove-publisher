import { eventChannel } from 'redux-saga'
import { fork, take, call, Effect } from 'redux-saga/effects'

export const channel = (host: Function) =>
  eventChannel((emitter) => {
    const pipe = (args: any[]) => {
      emitter(args || {})
    }

    host(pipe)

    return () => {}
  })

export function* takeFirst(pattern: string, saga: any, ...args: any[]) {
  // @ts-ignore
  const task = yield fork(function* () {
    while (true) {
      const action: { type: string, payload: any } = yield take(pattern)
      yield call(saga, ...args.concat(action))
    }
  })

  return task
}
