import React, {  useEffect, useState } from "react";
import useFetch from 'use-http'

export default function SelectField({field,register}) {

    const { get } = useFetch()
    const [data, setData] = useState([])
    const onChange = async () => {
        if(data.length < 1) {
            const respose = await get(`leads`)
            setData(respose.data)
        }
    }

    if(typeof field.source === 'string'){
        return (
            <select {...register(field.field)} className="form-control" onClick={onChange} >
            { data && data.length > 0 && <option key={-11} value={0}>-Select-</option> }
            { data && data.map((u,i) => {
                return (
                    <option key={i} value={u.id}>{u.email}</option>
                )
            })
            }
            </select>
        );
    }else{
        return (
            <select {...register(field.field)} className="form-control" >
                <option value="">-Select-</option>
            { field.source && field.source.map((u,i) => {
                return (
                    <option key={i} value={typeof u === 'object' ? u.id : u}>{typeof u === 'object' ? u.title : u}</option>
                )
            })
            }
            </select>
        );
    }
}
