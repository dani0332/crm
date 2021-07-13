import React, {  useEffect, useState } from "react";
import { useForm, Controller } from "react-hook-form";
import FormField from "../../components/field";
import {Error} from "../../modules/theme";
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from "yup";


export default function DrawForm({options}) {


 console.log('--------------options------------')
 console.log(options)
 console.log('--------------options------------')

// const { register, control ,handleSubmit, formState:{ errors } } = useForm({
//     resolver: yupResolver(options.schema)
//   });
const {  control ,handleSubmit, formState:{ errors } } = useForm();
const onSubmit = data => search(data);

console.log(errors)

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
                        name={u.field}
                        control={control}
                        rules={{ required: true }}
                        render={({ field }) => <FormField field={u} controller={{...field}} />}
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
