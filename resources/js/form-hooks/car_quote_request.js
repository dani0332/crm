import { put } from 'redux-saga/effects';

export function* afterFetch(options) {
  const { initialForm, response, url } = options;
  if (initialForm?.context === 'root' && response?.data.length < 1) {
    if (url.includes('filter')) {
      yield put({
        type: 'MessageShow',
        obj: { title: 'Info', message: 'No records found.', type: 'info' },
      });
    }
  }
}
