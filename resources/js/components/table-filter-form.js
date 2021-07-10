import React, {  useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import FormField from "./field";
import useFetch from 'use-http'

export default function TableFilter({filter, url, search}) {

const { register, handleSubmit, formState: { errors } } = useForm();
const onSubmit = data => search(data);

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
