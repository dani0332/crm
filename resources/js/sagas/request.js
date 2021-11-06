import { put } from 'redux-saga/effects';
import { getFormHook } from '../form-hooks';

export function* sendRequest(url, request, options) {
  try {
    const response = yield fetch(url, request);
    if (!response.ok) {
      if (options?.reject && typeof options.reject === 'function') {
        yield put({
          type: 'MessageShow',
          obj: {
            title: 'ERROR',
            message:
              'Something wrong with your request. Please contact with administration.',
            type: 'danger',
          },
        });
        options?.reject({ code: response.status, response: response });
        return;
      } else throw { code: response.status, response: response };
    }

    const data = yield response.json();
    const hook = yield getFormHook({ form: options?.context?.form });
    if (hook && request?.method.toLowerCase() === 'get')
      yield hook.afterFetch({
        response: data,
        initialForm: options?.context,
        url: url,
      });
    console.log(`**************${url}->data ******************`);
    console.log(data);
    console.log(`**************${url}->data ******************`);
    if (options?.resolve && typeof options.resolve === 'function')
      options.resolve(data);
    else return data;
  } catch (error) {
    if (options?.reject && typeof options.reject === 'function')
      options.reject(error);
    else return error;
    console.log(`**************${url}->Error ******************`);
    console.log(error);
    console.log(`**************${url}->Error ******************`);
  }
}
