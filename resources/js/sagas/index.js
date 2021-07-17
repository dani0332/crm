// Imports: Dependencies
import { all, fork, takeEvery, put } from 'redux-saga/effects';
import { watchEveryRequest } from './watch-saga'

export function* monitor(obj) {
  yield takeEvery('*', (obj) => {
    console.log('--------------monitor---------------')
    console.log(obj?.type)
    console.log('--------------monitor---------------')
  })
}

// Redux Saga: Root Saga
export function* rootSaga() {
  yield all([
    fork(watchEveryRequest),
  ]);
};

export function dispatchPromise(obj) {
  return new Promise((resolve, reject) => {
    const { dispatch, options } = obj
    options.resolve = resolve
    options.reject = reject
    dispatch(options)
  });
}
