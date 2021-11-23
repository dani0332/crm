import React, { useEffect, useReducer, useRef } from 'react';
import { useForm, Controller } from 'react-hook-form';
import FormField from '../../components/field';
import { Error } from '../../modules/theme';
import { useDispatch } from 'react-redux';
import cleanDeep from 'clean-deep';
import { useParams } from 'react-router-dom';
import FormContext from './FormContext';
import { session } from '../../utils';
import DrawSubform from './draw-subform';
import randomString from '@smakss/random-string';

const initialState = {
  fields: [],
  sections: [],
};

function makeNullHideFields(state) {
  let data = {};
  const { fields, selectedRecord } = state;
  Object.entries(fields).forEach(entry => {
    const [key, value] = entry;
    const getField = value;
    const recVal = selectedRecord?.[key];

    if (recVal && getField?.if) {
      let getVal = recVal;
      if (typeof getField?.transform === 'function')
        getVal = getField?.transform(recVal);

      let val = getField.if[getVal?.selected] ? getField.if[getVal.selected]: getField.if[getVal.value];
      if (val?.fields) {
        const getCondFields = val.fields;
        if (getCondFields.length > 0) {
          getCondFields.forEach(() => {});
        }
      } else {
        const getCondFields = getField.else;
        getCondFields.forEach(element => {
          data[element] = '';
        });
      }
    }
  });
  return data;
}

function conditionState(state, action) {
  const {
    field: { name, selected, value },
  } = action;
  const getField = state.fields[name];
  if (getField?.if) {
    const calcVal = (typeof value === 'object') ? value?.value : selected
    const val = getField.if[calcVal];
    if (val?.fields) {
      const getCondFields = getField.if[calcVal].fields;
      if (getCondFields.length > 0) {
        getCondFields.forEach(element => {
          state.fields[element].offscreen = false;
        });
      }
    } else {
      const getCondFields = getField.else;
      getCondFields.forEach(element => {
        state.fields[element].offscreen = true;
      });
    }
  }
}

function reducer(state, action) {
  switch (action.type) {
    case 'clear':
      return initialState;
    case 'reset': {
      return { ...action.state };
    }
    case 'setValue': {
      const {
        field: { name, value },
      } = action;
      const getField = state.fields[name];
      getField.value = value;
      if (state?.action_type === 'new') {
        console.log('------draw-form.js---Adding---draw-form.js-------');
        console.log(state);
        console.log('------draw-form.js---Adding---draw-form.js-------');
      }
      if (
        state?.action_type === 'edit' &&
        state?.selectedRecord &&
        state?.selectedRecord?.[name]
      ) {
        conditionState(state, action);
        state.selectedRecord[name] = value;
      } else if (state?.action_type === 'edit') {
        conditionState(state, action);
      }

      console.log('------Return-------');
      console.log(state);
      console.log('------Return-------');

      return { ...state };
    }
    default:
      return {};
  }
}

function DrawForm(props) {
  const Context = React.useContext(FormContext);
  const { manageListDispatch, initialForm } = Context;
  //const { options, manageListDispatch } = props
  const { options } = props;
  const [state, dispatch] = useReducer(reducer, options);
  const dispatch_ = useDispatch();
  const propsRef = useRef();
  const params = useParams();
  const {
    control,
    handleSubmit,
    formState: { errors },
  } = useForm({ shouldUnregister: true });
  const onSubmit = data => {
    
    console.log('------------getData----------')
    console.log(data);
    console.log('------------getData----------');

    if (typeof state?.postTransform === 'function') {
      console.log('*****onSubmit-Transform-Data-draw-form.js*************');
      let transformData = state?.postTransform({
        data: data,
        state: { state, ...initialForm },
        params: params,
      });
      dispatch_({
        type: 'VISIBLE_FORM_SAVE',
        selectedRecord: state?.selectedRecord,
        body: { ...cleanDeep(transformData) },
        initialForm: initialForm,
        manageListDispatch: manageListDispatch,
      });
    } else {
      dispatch_({
        type: 'VISIBLE_FORM_SAVE',
        selectedRecord: state?.selectedRecord,
        body: { ...cleanDeep(data), ...getData },
        initialForm: initialForm,
        manageListDispatch: manageListDispatch,
      });
    }
  };

  useEffect(async () => {
    console.log('********************useEffect********************');
    console.log(options);
    console.log(propsRef.current);
    if (options && propsRef?.current) {
      console.log(
        '**************State draw-form.js-props.current*****************',
      );
      console.log(options);
      console.log(
        '**************State draw-form.js-props.current*****************',
      );

      let resetting = false;
      if (options.action_type !== propsRef.current.action_type) {
        dispatch({ type: 'reset', state: { ...options } });
        resetting = true;
      } else if (options?.title !== propsRef.current?.title) {
        resetting = true;
        dispatch({ type: 'reset', state: { ...options } });
      }

      if (resetting === true) {
        console.log('*****************Reset (draw-form.js)*******************');
        console.log(options);
        console.log(propsRef?.current);
        console.log('*****************Reset(draw-form.js)*******************');
      }
    } else {
      console.log('**************State draw-form.js*****************');
      console.log(state);
      console.log('**************State draw-form.js*****************');
    }
    propsRef.current = options;
  }, [options, state]);

  const { role } = session();

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className='col-md-12'>
        {state &&
          state.sections.map((section, i) => {
            return (
              <div className='' key={i}>
                <div className='x_title'>
                  <h2>{section.label}</h2>
                  <div className='clearfix'></div>
                </div>
                <div className='x_content'>
                  {section &&
                    section.fields.map((u, i) => {
                      if (state.fields[u]?.access) {
                        const access = state.fields[u]?.access;
                        const formState = state?.action_type;
                        let getAccess = [];
                        if (formState === 'new') getAccess = access?.write;
                        if (formState === 'edit') getAccess = access?.update;
                        if (formState === 'read') getAccess = access?.read;
                        if (!getAccess || !getAccess.includes(role)) {
                          return <div></div>;
                        }
                      }

                      let value = '';
                      if (state.fields[u]?.value) {
                        value = state.fields[u].value;
                      } else if (
                        state?.selectedRecord &&
                        state?.selectedRecord?.[u]
                      ) {
                        value = state.selectedRecord[u];
                      }
                      const dslField = {
                        ...state.fields[u],
                        selectedRecord: state?.selectedRecord,
                        value: value,
                        dispatch: dispatch,
                        formState: state?.action_type,
                        field: u,
                        random: randomString(),
                      };
                      let rules = dslField.rules ? dslField.rules : {};
                      let fieldVal = null;
                      if (
                        state?.action_type === 'read' ||
                        state?.action_type === 'edit'
                      ) {
                        fieldVal =
                          typeof dslField?.value === 'object'
                            ? dslField.value.id
                            : dslField?.value;
                      }

                      if (state.fields[u]?.offscreen === true) {
                        return <div key={i}></div>;
                      }

                      return (
                        <div className='item form-group' key={i}>
                          {
                            dslField.type === 'subform' && (
                              <DrawSubform
                                control={control}
                                errors={errors}
                                key={`formfield-subform-${i}`}
                                field={dslField}
                                Controller={Controller}
                              />
                            )
                            //<FormField control={control} errors={errors} key={`formfield-subform-${i}`} field={dslField} Controller={Controller}  />
                          }
                          <label className='col-form-label col-md-3 col-sm-3 label-align'>
                            {dslField.label}
                            {rules?.required && (
                              <span className='required'> * </span>
                            )}
                          </label>
                          <div className='col-md-6 col-sm-6 '>
                            {dslField.type !== 'subform' && (
                              <Controller
                                key={i}
                                name={u}
                                shouldUnregister={true}
                                control={control}
                                defaultValue={fieldVal}
                                rules={rules}
                                render={({ field }) => (
                                  <FormField
                                    key={`formfield-${i}`}
                                    field={dslField}
                                    controller={{ ...field }}
                                  />
                                )}
                              />
                            )}
                            {errors[u] && errors[u].type === 'required' && (
                              <Error>
                                <p>Required.</p>
                              </Error>
                            )}
                            {errors[u] && errors[u].type === 'maxLength' && (
                              <Error>
                                <p>maxLength.</p>
                              </Error>
                            )}
                          </div>
                        </div>
                      );
                    })}
                </div>
              </div>
            );
          })}
      </div>
      {state?.readOnly !== true && (
        <div className='col-md-12'>
          <div className='ln_solid'></div>
          <div className='pull-right'>
            <button
              className='btn btn-primary'
              type='reset'
              onClick={() =>
                dispatch_({
                  type: 'VISIBLE_FORM',
                  formState: 'list',
                  initialForm: initialForm,
                  manageListDispatch: manageListDispatch,
                })
              }
            >
              Cancel
            </button>
            <button type='submit' className='btn btn-success'>
              Save
            </button>
          </div>
        </div>
      )}
    </form>
  );
}

export default DrawForm;
