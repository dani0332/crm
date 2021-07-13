import React, {  useEffect, useState } from "react";
import { useForm, Controller } from "react-hook-form";
import FormField from "../../components/field";
import {Error} from "../../modules/theme";



export default function DrawForm({ options }) {
// const { register, control ,handleSubmit, formState:{ errors } } = useForm({
//     resolver: yupResolver(options.schema)
//   });
const { control ,handleSubmit ,formState: { errors }, reset, clearErrors } = useForm( {shouldUnregister: true });
const onSubmit = data => console.log(data);

useEffect(() =>  {
    clearErrors()
},[]);

return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className="col-md-12">
      {options && options.fields.map((u,i) => {
          return (
            <div className="item form-group" key={i}>
                <label className="col-form-label col-md-3 col-sm-3 label-align" >
                {u.label}
                    <span className="required">*</span>
                </label>
                <div className="col-md-6 col-sm-6 ">
                    <Controller
                        key={i}
                        name={u.field}
                        control={control}
                        defaultValue={u.defaultValue}
                        shouldUnregister={true}
                        rules={{ required: true }}
                        render={({ field  }) => <FormField field={u} controller={{...field}} />}
                    />
                     {/* <FormField field={u} register={register} /> */}
                     {errors[u.field] && errors[u.field].type === "required" && <Error><p>Required.</p></Error>}
                     {/* {errors[u.field] && <Error><p>{errors[u.field].message}.</p></Error>} */}
                </div>
            </div>
          )
      })
      }
      </div>
      <div className="col-md-12">
      <input type="submit" />
      </div>
    </form>
  );
}
