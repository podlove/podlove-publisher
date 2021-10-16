import { combineReducers } from 'redux'
import * as lifecycleStore from './lifecycle.store';
import * as chaptersStore from './chapters.store';

export default combineReducers({
  lifecycle: lifecycleStore.reducer,
  chapters: chaptersStore.reducer
})
