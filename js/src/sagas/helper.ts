import { eventChannel } from 'redux-saga'
export const channel = (host: Function) =>
  eventChannel((emitter) => {
    const pipe = (args: any[]) => {
      emitter(args || {})
    }

    host(pipe)

    return () => {}
  })
