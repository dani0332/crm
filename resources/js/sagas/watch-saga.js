// Imports: Dependencies
import { delay, takeEvery, put, select, fork, call } from 'redux-saga/effects'
import { sendRequest } from '../redux/request'

function* processRequest(obj) {
  const { post } = obj
  const { form } = post
  try {
    yield sendRequest(obj)
  }
  catch (error) {
    console.log(error);
  }
}

function* sendStatusRequest(obj) {
  const params = {
    method: "GET",
    action: 'status'
  }
  const options = {
    type: 'SEND_REQUEST',
    post: params
  }
  yield sendRequest(options)
}

export function* watchEveryRequest() {
  yield takeEvery('SEND_REQUEST', processRequest);
  yield takeEvery('STATUS', sendStatusRequest);
}
