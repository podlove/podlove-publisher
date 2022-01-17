import { eventChannel } from 'redux-saga'
import { fork, take, call } from 'redux-saga/effects'

export const channel = (host: Function) =>
  eventChannel((emitter) => {
    const pipe = (args: any[]) => {
      emitter(args || {})
    }

    host(pipe)

    return () => {}
  })

export function* takeFirst(pattern, saga, ...args) {
  const task = yield fork(function* () {
    while (true) {
      const action = yield take(pattern)
      yield call(saga, ...args.concat(action))
    }
  })
  return task
}
