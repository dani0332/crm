import React, {  useEffect, useReducer, forwardRef, useImperativeHandle } from "react";
import { useForm, Controller } from "react-hook-form";
import FormField from "../../components/field";
import {Error} from "../../modules/theme";

function reducer(state, action) {
    switch (action.type) {
      case 'reset':
        return action.state;
      default:
        throw new Error();
    }
  }

const DrawForm  = forwardRef((props, ref) => {
const { options, onSave } = props
const [state, dispatch] = useReducer(reducer, options);
const { control ,handleSubmit ,formState: { errors }, reset, clearErrors } = useForm( {shouldUnregister: true });
const onSubmit = data => onSave(data);

useImperativeHandle(ref, (form) => ({
reRender(formObj) {
    console.log('**********reRender*********')
    console.log(formObj)
    console.log('**********reRender*********')
    dispatch({type: 'reset', state: formObj })
}

}));

return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className="col-md-12">
      {state && state.sections.map((section,i) => {
          return (
            <div className="" key={i}>
                <div className="x_title">
                    <h2>{section.label}</h2>
                    <div className="clearfix"></div>
                </div>
                <div className="x_content">
                { section && section.fields.map((u,i) => {
                    const dslField = state.fields[u]
                    const rules = dslField.rules ? dslField.rules : {}
                    return (
                        <div className="item form-group" key={i}>
                            <label className="col-form-label col-md-3 col-sm-3 label-align" >
                            {dslField.label}
                                <span className="required">*</span>
                            </label>
                            <div className="col-md-6 col-sm-6 ">
                                <Controller
                                    key={i}
                                    name={dslField.field}
                                    control={control}
                                    defaultValue={dslField.defaultValue}
                                    shouldUnregister={true}
                                    rules={rules}
                                    render={({ field  }) => <FormField field={dslField} controller={{...field}} />}
                                />
                                {/* <FormField field={u} register={register} /> */}
                                {errors[dslField.field] && errors[dslField.field].type === "required" && <Error><p>Required.</p></Error>}
                                {errors[dslField.field] && errors[dslField.field].type === "maxLength" && <Error><p>maxLength.</p></Error>}
                                {/* {errors[u.field] && <Error><p>{errors[u.field].message}.</p></Error>} */}
                            </div>
                        </div>
                    )
                })}
                </div>
            </div>
          );
      })
    }

      </div>
      <div className="col-md-12">
      <div className="ln_solid"></div>
        <div className="col-md-3 col-sm-3 offset-md-9 offset-sm-9">
            <button className="btn btn-primary" type="reset" onClick={ () => dispatch({type: 'decrement'})}>Cancel</button>
            <button type="submit" className="btn btn-success">Save</button>
        </div>
        </div>
    </form>
  );
})

export default DrawForm;
