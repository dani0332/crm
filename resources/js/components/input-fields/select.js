import React, {  useEffect, useState } from "react";
import useFetch from 'use-http'
import Select from 'react-select'

export default function SelectField({field,register, controller}) {

    let options = []


    //css-g1d714-ValueContainer
    //{ value: 'chocolate', label: 'Chocolate' },
    //{ value: 'strawberry', label: 'Strawberry' },
    //{ value: 'vanilla', label: 'Vanilla' }
    const { get } = useFetch()
    const [data, setData] = useState([])
    const onChange = async () => {
        if(data.length < 1) {
            const respose = await get(`leads`)
            setData(respose.data)
        }
    }

    //return (<Select options={options} />)

    if(typeof field.source === 'string'){
        options = data.map((u,i) => {
            return {value: u.id, label: u.email}
        })
        return (<Select options={options} {...controller} />)
       // return (
            // // <select {...register(field.field)} className="form-control" onClick={onChange} >
            // { data && data.length > 0 && <option key={-11} value={0}>-Select-</option> }
            // { data && data.map((u,i) => {
            //     return (
            //         <option key={i} value={u.id}>{u.email}</option>
            //     )
            // })
            // }
            // </select>
      //  );
    }else{

        options = field.source && field.source.map((u,i) => {
            return  { value: typeof u === 'object' ? u.id : u, label:typeof u === 'object' ? u.title : u }
        })

        console.log('---------select options------------')
        console.log(options)
        console.log('---------select options------------')

        return (<Select label="Single select"  options={options} {...controller}/>)
        // return (
        //     <select {...register(field.field)} className="form-control" >
        //         <option value="">-Select-</option>
        //     { field.source && field.source.map((u,i) => {
        //         return (
        //             <option key={i} value={typeof u === 'object' ? u.id : u}>{typeof u === 'object' ? u.title : u}</option>
        //         )
        //     })
        //     }
        //     </select>
        // );
    }
}
