import React, {  useEffect, useState } from "react";

export default function InputField({field, controller}) {

// useEffect(() => { controller.onChange(null); },[])
const [value,setValue] = useState('')
const onChange = event => {

    console.log('-------------value---------')
    console.log(event.target.value)
    console.log('-------------value---------')

    controller.onChange(event.target.value)
    setValue(event.target.value)
}
return (
    <input className="form-control" value={value}  onChange={onChange}  />
);
}
