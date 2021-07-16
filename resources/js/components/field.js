import React, {  useEffect, useState, useMemo } from "react";
import { useForm } from "react-hook-form";
import InputField from "./input-fields/input";
import SelectField from "./input-fields/select";
import File from "./input-fields/file";
import DatePickerField from "./input-fields/date-picker";

export default function FormField({field, controller}) {
 const getComponent = (field) => {
    let compute = []
    switch(field.type){
        case 'text':
            compute.push(<InputField field={field} controller={controller}/>);
            break;
        case 'dropdown':
            compute.push(<SelectField field={field}  controller={controller} />);
            break;
        case 'file':
            compute.push(<File field={field}  controller={controller} />);
            break;
        case 'datePicker':
            compute.push(<DatePickerField field={field}  controller={controller} />);
            break;
    }
    return compute
}
const comp =  useMemo(() => getComponent(field), [field]);
return comp;
}

