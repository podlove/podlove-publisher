import { eventChannel, END } from 'redux-saga'
import { call, takeEvery, put } from 'redux-saga/effects'

import * as lifecycle from '@store/lifecycle.store'

function episodeSaga() {
  return function* () {
    const saveChannel = yield call(clickListener, 'button.editor-post-publish-button')
    takeEvery(saveChannel, save)
  }
}

function* save() {
  yield put(lifecycle.save())
}

function clickListener(selector: string) {
  return eventChannel(emitter => {
    let target

    const eventListener = (event: MouseEvent) => {
      emitter(event)
    }

    window.addEventListener('load', () => {
      target = document.querySelector(selector);
      target.addEventListener('click', eventListener);
    })

    return () => {
      target?.removeEventListener('click', eventListener)
      emitter(END)
    }
  })
}

export default episodeSaga
