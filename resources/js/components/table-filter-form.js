import React, {  useEffect, useState } from "react";
import { useForm, Controller } from "react-hook-form";
import FormField from "./field";
import useFetch from 'use-http'
import cleanDeep from "clean-deep";
import { session } from "../utils";

export default function TableFilter({filter, search}) {

const {control, handleSubmit, formState: { errors } } = useForm();
const onSubmit = data => search(cleanDeep(data))

  const { role , id} = session()
  console.log('----filter-----')
  console.log(filter)
  console.log('----filter-----')

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <div className="col-md-12">
      {filter && filter.map((u,i) => {
        if(u?.access) {
            const access = u.access
            let getAccess = []
            getAccess = access?.read
            if(!getAccess.includes(role)){
                return (<div></div>)
            }
        }

          //role
          return (
            <div className="col-md-6" key={i}>
                <div className="form-group row">
                    <label className="col-form-label col-md-3 col-sm-3 ">
                     {u.label}
                    </label>
                    <div className="col-md-7 col-sm-7 ">
                     {/* <FormField field={u} register={register} /> */}
                     <Controller
                        key={i}
                        name={u.field}
                        control={control}
                        defaultValue={u.defaultValue}
                        shouldUnregister={true}
                        render={({ field  }) => <FormField field={u} controller={{...field}} />}
                    />
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
        <div className="pull-right">
            <button className="btn btn-primary" type="reset" onClick={()=>onSubmit({})}>Reset</button>
            <button type="submit" className="btn btn-success">Search</button>
        </div>
        </div>

    </form>
  );
}
