import { eventChannel, END } from 'redux-saga'
import { call, takeEvery, put } from 'redux-saga/effects'

import * as lifecycle from '@store/lifecycle.store'

function lifecycleSaga() {
  return function* () {
    const saveChannel = yield call(clickListener, 'click', 'button.editor-post-publish-button')
    yield takeEvery(saveChannel, save)
  }
}

function* save() {
  yield put(lifecycle.save())
}

function clickListener(eventName: string, selector: string) {
  return eventChannel(emitter => {
    let target

    const eventListener = (event: MouseEvent) => {
      emitter(event)
    }

    window.addEventListener('load', () => {
      target = document.querySelector(selector);
      target.addEventListener(eventName, eventListener);
    })

    return () => {
      target?.removeEventListener(eventName, eventListener)
      emitter(END)
    }
  })
}

export default lifecycleSaga
