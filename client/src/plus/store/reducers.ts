import { combineReducers } from 'redux'
import * as lifecycleStore from '../../store/lifecycle.store'
import * as runtimeStore from '../../store/runtime.store'
import * as plusFileMigrationStore from '../../store/plusFileMigration.store'
import * as plusStore from '../../store/plus.store'

export default combineReducers({
  lifecycle: lifecycleStore.reducer,
  runtime: runtimeStore.reducer,
  plusFileMigration: plusFileMigrationStore.reducer,
  plus: plusStore.reducer,
})
