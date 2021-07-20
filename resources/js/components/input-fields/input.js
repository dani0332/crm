import React, {  useEffect, useState } from "react";

export default function InputField({field, controller}) {

const [value,setValue] = useState(field?.value)
const onChange = event => {
    console.log('-------------value---------')
    console.log(event.target.value)
    console.log('-------------value---------')
    controller.onChange(event.target.value)
    setValue(event.target.value)
}

return (
    <input className="form-control" disabled={field?.readOnly ? true : false} value={value}  onChange={onChange}  />
);
}
