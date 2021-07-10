import React, {  useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import FormField from "../../components/field";
import {Error} from "../../modules/theme";
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from "yup";

export default function DrawForm({options}) {


 console.log('--------------options------------')
 console.log(options)
 console.log('--------------options------------')

const { register, handleSubmit, formState:{ errors } } = useForm({
    resolver: yupResolver(options.schema)
  });
const onSubmit = data => search(data);

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
                     <FormField field={u} register={register} />
                     {errors[u.field] && <Error><p>{errors[u.field].message}.</p></Error>}
                </div>
            </div>
          )
      })
      }
      </div>
      <div className="col-md-12">
      <div className="ln_solid"></div>
        <div className="col-md-3 col-sm-3 offset-md-9 offset-sm-9">
            <button className="btn btn-primary" type="reset">Reset</button>
            <button type="submit" className="btn btn-success">Search</button>
        </div>
        </div>

    </form>
  );
}
