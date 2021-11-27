import { takeEvery } from 'redux-saga';
import { select } from 'redux-saga/effects';
import { selectors } from '@store';
import * as lifecycle from '@store/lifecycle.store';
import * as chapters from '@store/chapters.store';

function chaptersSaga(episodeForm: HTMLElement) {
  const chaptersForm = <HTMLTextAreaElement>document.createElement('textarea');
  chaptersForm.setAttribute('name', '_podlove_meta[chapters]')
  chaptersForm.style.display = 'none'
  episodeForm.append(chaptersForm)

  return function* () {
    yield takeEvery([lifecycle.INIT, chapters.UPDATE], syncForm, chaptersForm)
  }
}

function* syncForm(form: HTMLTextAreaElement) {
  const chapters = yield select(selectors.chapters.list);
  form.value = chapters.map(({ start, title }) => `${start} ${title}`).join(' ')
}

export default chaptersSaga;
