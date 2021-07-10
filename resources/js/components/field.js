import React, {  useEffect, useState, useMemo } from "react";
import { useForm } from "react-hook-form";
import InputField from "./input-fields/input";
import SelectField from "./input-fields/select";
import File from "./input-fields/file";

export default function FormField({field, register}) {
 const getComponent = (field) => {
    let compute = []
    switch(field.type){
        case 'text':
            compute.push(<InputField field={field} register={register}/>);
            break;
        case 'dropdown':
            compute.push(<SelectField field={field} register={register} />);
            break;
        case 'file':
            compute.push(<File field={field} register={register} />);
            break;
    }
    return compute
}
const comp =  useMemo(() => getComponent(field), [field]);
return comp;
}

