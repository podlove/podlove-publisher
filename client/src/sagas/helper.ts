import { eventChannel, Channel, channel as reduxChannel } from 'redux-saga'
import { fork, take, call, select, spawn, cancelled, put } from 'redux-saga/effects'
import { AxiosProgressEvent } from 'axios'

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
      const action: { type: string; payload: any } = yield take(pattern)
      yield call(saga, ...args.concat(action))
    }
  })

  return task
}

export function sleep(sec: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, sec * 1000))
}

export function* waitFor(selector: any) {
  const tester: boolean = yield select(selector)
  if (tester) return // (1)

  while (true) {
    yield take('*')
    const tester: boolean = yield select(selector)
    if (tester) return // (1b)
  }
}

export type ProgressPayload = {
  key: string
  progress: number
}

export interface ProgressData {
  key: string
  progress: number
}

export function* watchProgressChannel(
  progressChannel: Channel<ProgressData>,
  progressAction: Function
) {
  try {
    while (true) {
      const payload: ProgressPayload = yield take(progressChannel)

      // TODO: reset when selecting a file
      // TODO: reset when using the source picker
      if (progressAction.constructor.name === 'GeneratorFunction') {
        yield call(function* () {
          yield* progressAction(payload)
        })
      } else {
        const action = progressAction(payload)
        if (action) {
          yield put(action)
        }
      }
    }
  } finally {
    if ((yield cancelled()) as boolean) {
      progressChannel.close()
    }
  }
}

export function* createAndWatchProgressChannel(progressAction: Function) {
  const progressChannel: Channel<ProgressData> = yield call(reduxChannel)
  yield spawn(watchProgressChannel, progressChannel, progressAction)

  return progressChannel
}

export const createProgressHandler = (progressChannel: Channel<ProgressData>) => {
  return (key: string) => (progressEvent: AxiosProgressEvent) => {
    if (progressEvent.total) {
      const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      const payload: ProgressPayload = { key, progress: percentCompleted }

      progressChannel.put(payload)
    }
  }
}
