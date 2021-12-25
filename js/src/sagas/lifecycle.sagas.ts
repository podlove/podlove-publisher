import { eventChannel, END, EventChannel } from 'redux-saga'
import { call, takeEvery, put } from 'redux-saga/effects'

import * as lifecycle from '@store/lifecycle.store'

function lifecycleSaga(): () => any {
  return function* () {
    const saveChannel: EventChannel<any> = yield call(clickListener, 'click', 'button.editor-post-publish-button')
    yield takeEvery(saveChannel, save)
  }
}

function* save() {
  yield put(lifecycle.save())
}

function clickListener(eventName: string, selector: string) {
  return eventChannel(emitter => {
    let target: HTMLElement

    const eventListener = (event: MouseEvent) => {
      emitter(event)
    };

    window.addEventListener('load', () => {
      target = document.querySelector(selector) as HTMLElement;
      target.addEventListener(eventName, eventListener as EventListener);
    })

    return () => {
      target?.removeEventListener(eventName, eventListener as EventListener)
      emitter(END)
    }
  })
}

export default lifecycleSaga
