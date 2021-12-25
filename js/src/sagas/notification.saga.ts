import { takeEvery } from 'redux-saga/effects'

import { get } from 'lodash'
import { NOTIFY } from '@store/notification.store'

function errorSaga() {
  return function* () {
    yield takeEvery(NOTIFY, showNotification)
  }
}

function* showNotification(action: { type: string, payload: { type: 'success' | 'info' | 'error' | 'warning', message: string } }) {
  const dispatch = get(globalThis, ['wp', 'data', 'dispatch'])

  if (dispatch) {
    wordPressError(dispatch, get(action, ['payload']))
  } else {
    consoleError(action.payload)
  }
}

function wordPressError(
  dispatch: Function,
  { type, message }: { type: 'success' | 'warning' | 'error' | 'info'; message: string }
) {
  if (!message) {
    return
  }

  dispatch('core/notices').createNotice(
    type, // Can be one of: success, info, warning, error.
    message, // Text string to display.
    {
      type: 'snackbar',
      isDismissible: true, // Whether the user can dismiss the notice.
    }
  )
}

function consoleError({
  type,
  message,
}: {
  type: 'success' | 'warning' | 'error' | 'info'
  message: string
}) {
  switch (type) {
    case 'success':
    case 'info':
      console.log(message)
      break
    case 'warning':
      console.warn(message)
      break
    case 'error':
      console.error(message)
      break
  }
}

export default errorSaga
