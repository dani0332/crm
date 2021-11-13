// Imports: Dependencies
import { delay, takeEvery, put } from 'redux-saga/effects';
import { sendRequest } from './request';
import { getFormObjForDraw } from '../forms_dsl';
import { fillConditionalFields } from './prefill-conditional-fields';

export const getVisibleForm = state => state.visibleForm;

function* processRequest(obj) {
  const { selectedRecord, body, manageListDispatch, initialForm } = obj;

  console.log('**************ProcessRequest**********');
  console.log(selectedRecord);
  console.log('**************ProcessRequest**********');

  const formVisible = getFormObjForDraw(initialForm);
  const { db_table } = formVisible;
  manageListDispatch({ type: 'showLoader', obj: { loader: true } });
  let url = ``;
  let request = {};
  if (selectedRecord && selectedRecord?.id) {
    // update request
    url = `/form/${db_table}/${selectedRecord.id}`;
    request = {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
    };
  } else {
    url = `/form/${db_table}`;
    request = {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
    };
  }
  request.body = JSON.stringify(body);

  try {
    const response = yield sendRequest(url, request);
    manageListDispatch({ type: 'showLoader', obj: { loader: false } });
    if (response?.code === 500 || response?.code === 400) {
      console.log('------------response---------------');

      let showMsg = {
        title: 'ERROR',
        message:
          'Something wrong with your request. Please contact with administration',
      };
      if (response?.response) {
        const obj = yield response.response.json();
        const data = obj.data;
        console.log(data);
        showMsg.title = data?.title ? data.title : showMsg.title;
        showMsg.message = data?.message ? data.message : showMsg.message;
      }

      //console.log(data)
      console.log('------------response---------------');
      yield put({
        type: 'MessageShow',
        obj: { title: showMsg.title, message: showMsg.message, type: 'danger' },
      });
    }
    // const hook = yield getFormHook( { form: db_table } )
    // if(hook)
    //     yield hook.afterSave({ response: response, initialForm, body })
    yield processVisibleFormStates({ ...obj, formState: 'list' });
  } catch (error) {
    manageListDispatch({ type: 'showLoader', obj: { loader: false } });
    processVisibleFormStates({ obj: obj, formState: 'list' });
  }
}

function* processGetRequest(obj) {
  const {
    request: { url, method = 'GET', ...rest },
    reject,
    resolve,
    context,
  } = obj;

  const data = yield sendRequest(
    url,
    {
      method: method,
      headers: { 'Content-Type': 'application/json' },
      ...rest,
    },
    { reject, resolve, context },
    console.log(data),
  );
}

function* processVisibleFormStates(obj) {
  const {
    formState,
    selectedRecord,
    manageListDispatch,
    initialForm,
    override,
  } = obj;
  let session = {};
  if (localStorage.getItem('session') !== null) {
    session = JSON.parse(localStorage.getItem('session'));
  }
  console.log(
    '**************processVisibleFormStates*-WatchSaga.js*****************',
  );
  console.log(obj);
  console.log(initialForm?.override);
  console.log(override);
  console.log(
    '**************processVisibleFormStates**WatchSaga.js****************',
  );

  switch (formState) {
    case 'list':
      {
        const initialReducerState = getFormObjForDraw(initialForm);
        const getForm = {
          ...initialReducerState,
          ...initialForm,
          action_type: 'list',
          ...initialForm?.override,
          session: session,
        };
        manageListDispatch({ type: 'showLoader', obj: { loader: true } });
        console.log('**************List-watch-saga.js******************');
        console.log(override);
        console.log(getForm);
        console.log('**************List-watch-saga.js******************');

        if (getForm?.multi === false) {
          console.log('*************m***MultiFalse******************');
          const { db_table } = getForm;
          let filter = '';
          if (typeof initialForm?.filter === 'object') {
            let queryParamObj = { filter: JSON.stringify(initialForm?.filter) };
            const params = new URLSearchParams(queryParamObj);
            filter = `/?${params.toString()}`;
          } else if (typeof initialForm?.filter === 'string') {
            filter = `${initialForm?.filter}`;
          } else {
            filter = ``;
          }

          const objUrl = `/form/${db_table}${filter}`;
          const data = yield sendRequest(objUrl, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
          });
          const initialFormState = getFormObjForDraw(initialForm);
          if (data) {
            let rec = {};
            if (Array.isArray(data.data) && data.data.length > 0)
              rec = data.data[0];
            else rec = data.data;

            if(typeof initialReducerState.view?.events?.transformAfterFetchFromServer === 'function' ) {
              rec = initialReducerState.view.events.transformAfterFetchFromServer({formState,getForm },  rec)
            }
            const obj = {
              ...initialFormState,
              view_mode: 'form',
              data: rec,
              selectedRecord: rec,
              readOnly: true,
              action_type: 'read',
            };
            manageListDispatch({ type: 'showLoader', obj: { loader: false } });
            yield fillConditionalFields(obj);
            manageListDispatch({ type: 'read', obj });
          } else {
            const obj = {
              ...initialFormState,
              view_mode: 'form',
              selectedRecord: null,
              readOnly: true,
              action_type: 'read',
            };
            manageListDispatch({ type: 'showLoader', obj: { loader: false } });
            manageListDispatch({ type: 'read', obj });
          }
        } else {
          manageListDispatch({ type: 'showLoader', obj: { loader: false } });
          manageListDispatch({ type: 'list', obj: getForm });
        }
      }
      break;

    case 'cancel':
      {
        const initialReducerState = getFormObjForDraw(initialForm);
        const obj = {
          ...initialReducerState,
          ...initialForm,
          view_mode: 'list',
          action_type: 'list',
        };
        manageListDispatch({ type: 'list', obj });
      }
      break;
    case 'delete':
      {
        console.log('***********delete***********');
        console.log(selectedRecord);
        console.log(initialForm);
        console.log('***********delete***********');
        manageListDispatch({ type: 'showLoader', obj: { loader: true } });
        const formVisible = getFormObjForDraw(initialForm); //yield select(getVisibleForm)
        console.log('**************Delete-watch-saga.js******************');
        console.log(formVisible);
        // const { db_table } = formVisible;
        if (!selectedRecord) {
          const newState = { ...formVisible, data: {} };
          const obj = {
            ...newState,
            view_mode: 'form',
            selectedRecord: {},
            readOnly: false,
            action_type: 'list',
          };
          manageListDispatch({ type: 'showLoader', obj: { loader: false } });
          manageListDispatch({ type: 'list', obj });
          return;
        }
        console.log('**************Delete-watch-saga.js******************');
        // let objUrl = `/form/${db_table}/${selectedRecord.id}`;
        // const resp = yield fetch(objUrl, { method: 'Delete' });
        // const data = yield resp.json();
        const initialReducerState = getFormObjForDraw(initialForm);
        const getForm = {
          ...initialReducerState,
          ...initialForm,
          action_type: 'list',
        };
        manageListDispatch({ type: 'showLoader', obj: { loader: false } });
        manageListDispatch({ type: 'list', obj: getForm });
      }
      break;
    case 'new':
      {
        const initialReducerState = getFormObjForDraw(initialForm);
        const objNew = {
          ...initialReducerState,
          ...initialForm,
          view_mode: 'form',
          action_type: 'new',
        };
        //yield put({ type: 'new', obj })
        manageListDispatch({ type: 'new', obj: objNew });
      }
      break;
    case 'read':
      {
        manageListDispatch({ type: 'showLoader', obj: { loader: true } });
        const formVisible = getFormObjForDraw(initialForm); //yield select(getVisibleForm)
        const { db_table } = formVisible;
        if (!selectedRecord) {
          const newState = { ...formVisible, data: {} };
          const obj = {
            ...newState,
            view_mode: 'form',
            record: {},
            readOnly: true,
            action_type: 'read',
            ...initialForm?.override,
            session: session,
          };
          //yield put({ type: 'read', obj })
          manageListDispatch({ type: 'showLoader', obj: { loader: false } });

          console.log(
            '**************Read-Data!selectedRecord******************',
          );
          console.log(obj);
          console.log(
            '**************Read-Data!selectedRecord******************',
          );

          manageListDispatch({ type: 'read', obj });
        } else {
          let objUrl = `/form/${db_table}/${selectedRecord.id}`;
          const resp = yield fetch(objUrl, { method: 'GET' });
          const data = yield resp.json();
          const newState = { ...formVisible, data: data.data };
          const obj = {
            ...newState,
            view_mode: 'form',
            selectedRecord: selectedRecord,
            readOnly: true,
            action_type: 'read',
            ...initialForm?.override,
            session: session,
          };
          console.log('**************Read-watch-saga.js*****************');
          console.log(obj);
          console.log(formVisible);
          console.log(selectedRecord);
          console.log('**************Read-watch-saga.js******************');
          // yield put({ type: 'read', obj })
          manageListDispatch({ type: 'showLoader', obj: { loader: false } });

          console.log('**************Read-Data******************');
          console.log(obj);
          console.log('**************Read-Data******************');
          yield fillConditionalFields(obj);

          manageListDispatch({ type: 'read', obj });
        }
      }
      break;

    case 'edit':
      {
        manageListDispatch({ type: 'showLoader', obj: { loader: true } });
        const formVisible = getFormObjForDraw(initialForm); //yield select(getVisibleForm)
        console.log('**************Edit-watch-saga.js******************');
        console.log(formVisible);
        const { db_table } = formVisible;
        if (!selectedRecord) {
          const newState = { ...formVisible, data: {} };
          const obj = {
            ...newState,
            view_mode: 'form',
            selectedRecord: {},
            readOnly: false,
            action_type: 'edit',
            session: session,
          };
          manageListDispatch({ type: 'showLoader', obj: { loader: false } });
          manageListDispatch({ type: 'edit', obj });
          return;
        }
        console.log('**************Edit-watch-saga.js******************');
        let objUrl = `/form/${db_table}/${selectedRecord.id}`;
        const resp = yield fetch(objUrl, { method: 'GET' });
        const data = yield resp.json();
        const newState = { ...formVisible, data: data.data };
        const obj = {
          ...newState,
          view_mode: 'form',
          selectedRecord: selectedRecord,
          readOnly: false,
          action_type: 'edit',
          session: session,
        };
        manageListDispatch({ type: 'showLoader', obj: { loader: false } });

        console.log('**************Edit-Data******************');
        console.log(obj);
        console.log('**************Edit-Data******************');
        yield fillConditionalFields(obj);

        manageListDispatch({ type: 'edit', obj });
      }
      break;

    case 'reset':
      {
        console.log('**************Reset-watch-saga.js******************');
        console.log('**************Rest-watch-saga.js******************');
        yield put({ type: 'reset' });
      }
      break;
  }
}

function* me() {
  while (true) {
    try {
      const data = yield sendRequest('/users/me', {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' },
      });
      if (localStorage.getItem('session') === null) {
        localStorage.setItem('session', JSON.stringify(data));
      }
      yield delay(10000);
    } catch (err) {
      console.log(err);
    }
  }
}

export function* watchEveryRequest() {
  yield takeEvery('SEND_REQUEST', processGetRequest);
  yield takeEvery('VISIBLE_FORM', processVisibleFormStates);
  yield takeEvery('VISIBLE_FORM_SAVE', processRequest);
  yield me();
}
