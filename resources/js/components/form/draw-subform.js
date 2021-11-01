import React, { useReducer } from 'react';
import { getDSLForm } from '../../forms_dsl';
import FormField from '../field';
import { Error } from '../../modules/theme';
import { session } from '../../utils';
import randomString from '@smakss/random-string';

const initialState = {
  fields: [],
  sections: [],
};

function reducer(state, action) {
  switch (action.type) {
    case 'clear':
      return initialState;
    case 'setValue': {
      const {
        field: { name, value },
      } = action;
      const getField = state.fields[name];
      getField.value = value;

      console.log(
        '***************reducer--state-draw-subform-form.js-***************',
      );
      console.log(name);
      console.log(value);
      console.log(state);
      console.log(
        '***************reducer--state-draw-subform-form.js-***************',
      );

      if (state?.data && state?.data?.[name]) {
        console.log(
          '------draw-subform-form.js---Removing---draw-subform-form.js-----------',
        );
        state.data[name] = value;
      }
      return { ...state };
    }
    default:
      return {};
  }
}

export default function DrawSubform({ field, Controller, ...remaining }) {
  let options = getDSLForm({ form: field.form });
  options.data = field?.value ? field.value : {};
  const { control, errors } = remaining;
  const [state, dispatch] = useReducer(reducer, options);

  console.log('***************state-draw-subform-form.js-***************');
  console.log(state);
  console.log('***************state-draw-subform-form.js-***************');
  const { role } = session();
  return (
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
                      const formState = field?.formState;
                      let getAccess = [];
                      if (formState === 'new') getAccess = access.write;
                      if (formState === 'edit') getAccess = access.update;
                      if (formState === 'read') getAccess = access.read;

                      if (!getAccess.includes(role)) {
                        console.log(getAccess);
                        return <div></div>;
                      }
                    }

                    let value = '';
                    if (state.fields[u]?.value) {
                      value = state.fields[u].value;
                    } else if (state?.data && state?.data?.[u]) {
                      value = state.data[u];
                    }
                    const dslField = {
                      ...state.fields[u],
                      value: value,
                      dispatch: dispatch,
                      formState: field?.formState,
                      field: u,
                      random: randomString(),
                    };
                    const rules = dslField.rules ? dslField.rules : {};

                    let fieldVal = null;
                    if (
                      field?.formState === 'read' ||
                      field?.formState === 'edit'
                    ) {
                      fieldVal =
                        typeof dslField?.value === 'object'
                          ? dslField.value.id
                          : dslField?.value;
                    }

                    return (
                      <div className='item form-group' key={i}>
                        <label className='col-form-label col-md-3 col-sm-3 label-align'>
                          {dslField.label}
                          {rules?.required && (
                            <span className='required'> * </span>
                          )}
                        </label>
                        <div className='col-md-6 col-sm-6 '>
                          <Controller
                            key={i}
                            name={u}
                            shouldUnregister={true}
                            control={control}
                            defaultValue={fieldVal}
                            rules={{ required: true }}
                            render={({ field }) => (
                              <FormField
                                key={`subform-${i}`}
                                field={dslField}
                                controller={{ ...field }}
                              />
                            )}
                          />
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
  );
}
