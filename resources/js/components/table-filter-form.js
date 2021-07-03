import React, {  useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import FormField from "./field";

export default function TableFilter({filter, url}) {

const { register, handleSubmit, formState: { errors } } = useForm();
const onSubmit = data => console.log(data);

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className="col-md-12">
      {errors.exampleRequired && <span>This field is required</span>}
      {filter && filter.map((u,i) => {
          return (
            <div className="col-md-6" key={i}>
                <div className="form-group row">
                    <label className="col-form-label col-md-3 col-sm-3 ">
                     {u.label}
                    </label>
                    <div className="col-md-7 col-sm-7 ">
                     <FormField field={u} register={register} />
                    </div>
                    <div className="col-md-2 col-sm-2 "></div>
                </div>
            </div>
          )
      })
      }
      </div>
      <input type="submit" />
    </form>
  );
}
