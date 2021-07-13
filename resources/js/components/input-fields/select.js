import React, {  useEffect, useState } from "react";
import useFetch from 'use-http'
import Select from 'react-select'

export default function SelectField({field,register, controller}) {

    let options = []
    const { get } = useFetch()
    const [data, setData] = useState([])
    const onChange = selectedOptions => {
        controller.onChange(selectedOptions?.value)
    }
    if(typeof field.source === 'string'){
        options = data.map((u,i) => {
            return {value: u.id, label: u.email}
        })
        return (<Select options={options} {...controller} />)
    }else{

        options = field.source && field.source.map((u,i) => {
            return  { value: typeof u === 'object' ? u.id : u, label:typeof u === 'object' ? u.title : u }
        })
        return (<Select label="Single select"  options={options}  onChange={onChange}/>)
    }
}
