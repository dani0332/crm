import React, {  useEffect, useState, useRef } from "react";
import useFetch from 'use-http'
import Select from 'react-select'
import { dispatchPromise } from "../../sagas"
import { useSelector, useDispatch } from 'react-redux';
import { date } from "yup/lib/locale";

export default function SelectField({field, controller}) {


    console.log("********* -> Select-Render********->",field?.field)
    console.log(field)

    const dispatch = useDispatch()
    const propsRef = useRef()
    const [data, setData] = useState({ data: [], loading: false, defaultValue:{}, isDisabled: false })
    useEffect(() => {

        // console.log("---------------redraw------- ")
        // console.log(field)
        // console.log(propsRef?.current)
        // console.log("---------------redraw------- ")

        if(field && propsRef?.current ){
            if(field?.random !== propsRef?.current.random){
                propsRef.current = field
                redraw(field)
            }
        }else{
            propsRef.current = field
            redraw(field)
        }
     },[field])

    const redraw = (field) => {

        if( field?.formState === 'read' || field?.formState === 'edit' ) {

            let defaultValue = {}
            if(field?.value) {
                if (typeof field?.transform === 'function') {
                    defaultValue =  field?.transform(field?.value)
                } else {
                    defaultValue = { value: typeof field?.value === 'object' ? field.value.id : field?.value, label: typeof field?.value === 'object' ? field.value?.text : field?.value }
                }
            }
            setData({ data: [], isDisabled : field.formState === 'read' ? true : false, defaultValue: defaultValue, loading: false })

        } else if(typeof field?.source === 'string') {

            let defaultValue = []
            if(field?.value) {
                if (typeof field.transform === 'function')
                    defaultValue =  field.transform(field.value)
                else
                    defaultValue =  field.value
            }
            setData({ data: [], isDisabled : false, defaultValue: defaultValue, loading: false })

        } else {

            const options = field.source && field.source.map((u,i) => {
                return  { id: typeof u === 'object' ? u.id : u, text: typeof u === 'object' ? u.text : u }
            })

            let defaultValue = []
            if(field?.value)
                defaultValue =  { value: typeof field?.value === 'object' ? field.value.id : field?.value, label: typeof field?.value === 'object' ? field.value?.text : field?.value }
            setData({ data: options, isDisabled : false, defaultValue: defaultValue, loading: false })
        }

    }
    const onChange = selectedOptions => {
        console.log('---**********select.js-onChange-************----')
        console.log(selectedOptions)
        console.log('---**********select.js-onChange-************----')

        if (typeof field?.dispatch === 'function') {
            if(field?.if) // For condition fields
                field.dispatch({type : 'setValue', field: { name: field.field, value :  selectedOptions?.value }});
        }

        controller.onChange(selectedOptions.value);
        setData({...data, defaultValue: selectedOptions})
    }
    const onFocus = async () => {

        if(typeof field?.source !== 'string') {
            const options = field.source && field.source.map((u,i) => {
                return  { id: typeof u === 'object' ? u.id : u, text: typeof u === 'object' ? u.text : u }
            })

            let defaultValue = {}
            if(field?.value) {
                if (typeof field?.transform === 'function') {
                    defaultValue =  field?.transform(field?.value)
                } else {
                    defaultValue = { value: typeof field?.value === 'object' ? field.value.id : field.value, label: typeof field?.value === 'object' ? field.value?.text : field.value }
                }
            }

            setData({ ...data, data: options, isDisabled : false, defaultValue: defaultValue, loading: false })
            return
        }

        if(data.data.length < 1) {
            setData({ ...data, loading: true })
            const url = `/form/${field.source}`
            dispatchPromise({
                dispatch: dispatch,
                options: {
                    type: 'SEND_REQUEST',
                    request: { url: url }
                }
            }).then((response) => {
                setData({ ...data,  data: response.data, isDisabled : false, loading: false })
            }).catch(error => {
                setData({ ...data, data: [], isDisabled : false, loading: false })
            });
        }
        else if(field.formState === 'edit' && data.data.length < 1){

            setData({ ...data, loading: true })
            const url = `/form/${field.source}`
            dispatchPromise({
                dispatch: dispatch,
                options: {
                    type: 'SEND_REQUEST',
                    request: { url: url }
                }
            }).then((response) => {
                setData({ ...data,data: response.data, isDisabled : false,  loading: false })
            }).catch(error => {
                setData({ ...data, data: [], isDisabled : false, loading: false })
            });
        }
        else { return }
    }

    let value = []
    if (typeof field?.transform === 'function') {
        if(data.data.length > 0){
            value =  field?.transform(data.data)
        }
    }else{
        const options = data.data.map((u,i) => {
            return { value: u.id , label: u.text}
        })
        value = options
    }

    return (
            <Select
                options={value}
                value={data?.defaultValue}
                onChange={onChange}
                isDisabled={data.isDisabled}
                onFocus={onFocus}
                isLoading={date.loading}
                isSearchable={true}
                name={field?.field}
            />
        )
}
