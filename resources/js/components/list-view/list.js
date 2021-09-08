import React, {  useState, useEffect, useRef } from "react";
import styled from "styled-components";
import Table from "../../components/table";
import TableFilter from "../../components/table-filter-form";
import useFetch from 'use-http'
const Styles = styled.div``;
import { useHistory } from "react-router-dom";
import  { ScaleLoader } from "react-spinners";
import { useParams } from "react-router-dom";
import FormContext from "../form/FormContext";
import { dispatchPromise } from "../../sagas"

function List(props) {
//const { form, dispatch, manageListDispatch } = props
const { form, dispatch } = props

console.log("*********list.js***********")
console.log(form)
console.log("*********list.js***********")
const object =  { view:  form.view, db_table: form.db_table}

let objUrl = `/form/${object.db_table}`
const params = useParams()
let paramRef = useRef();
paramRef.current = params
const { events } = form.view

if( events && typeof events.applyFilter === 'function'){
    const filter = events.applyFilter({ form: form, url : `form/${object.db_table}`, dispatch: dispatch, history: history, params: params })
    objUrl = `/form/${object.db_table}${filter}`
}

const [table, setTable] = useState({columns:[] , data: [], loader: false})
const { get, loading } = useFetch()
const history = useHistory()

useEffect( async() => {

    setTable({ ...table , loader: true })
    dispatchPromise({
        dispatch: dispatch,
        options: {
            type: 'SEND_REQUEST',
            request: { url: objUrl }
        }
    }).then((response) => {
        if( events && typeof events.afterFetchData === 'function'){
            data = events.afterFetchData({ resp: response, url : objUrl, dispatch: dispatch, history: history })
            setTable({ columns: object.view.columns, data: response?.data, loader: true })
        }else{
            setTable({ columns: object.view.columns, data: response?.data, loader: false })
        }
    }).catch(error => {
        setTable({ columns: object.view.columns, data: [], loader: false })
    });

}, [objUrl]);


const doSearch = async (obj) => {

    let filter = { ...obj }
    if( events && typeof events.applyFilterAfterSearch === 'function'){
        filter = events.applyFilterAfterSearch({  form : form, dispatch: dispatch, params: paramRef.current, filter: filter })
    }

    let queryParamObj = { filter: JSON.stringify(filter) }
    const params = new URLSearchParams(queryParamObj);
    console.log(params.toString())
    const url = `/form/${object.db_table}/?${params.toString()}`

    setTable({ ...table , loader: true })
    dispatchPromise({
        dispatch: dispatch,
        options: {
            type: 'SEND_REQUEST',
            request: { url: url }
        }
    }).then((response) => {
        setTable({ columns: object.view.columns, data: response?.data, loader: false })
    }).catch(error => {
        setTable({ columns: object.view.columns, data: [], loader: false })
    });
}

return (
    <Styles>
        <Styles>
             <TableFilter  filter={object.view.find.basic}  search={doSearch} />
             { table.loader === true && <div className="sweet-loading"><ScaleLoader color={'#000000'} loading={true}   size={150} /></div> }
             <Table columns={table.columns} data={table.data} view={object.view} form={form}/>
        </Styles>
    </Styles>
  );
}

export default List;
