import { takeEvery } from 'redux-saga/effects'

import { get } from 'lodash'
import { error, ERROR } from '@store/lifecycle.store'


function errorSaga() {
  return function* () {
    yield takeEvery(ERROR, showError)
  }
}

function* showError(action: typeof error) {
  const dispatch = get(globalThis, ['wp', 'data', 'dispatch']);

  if (dispatch) {
    wordPressError(dispatch, get(action, ['payload', 'message']))
  } else {
    console.error(action.payload)
  }
}

function wordPressError(dispatch: Function, message: string) {
  if (!message) {
    return
  }

  dispatch("core/notices").createNotice(
    "error", // Can be one of: success, info, warning, error.
    message, // Text string to display.
    {
      type: "snackbar",
      isDismissible: true, // Whether the user can dismiss the notice.
    }
  );
}

export default errorSaga

