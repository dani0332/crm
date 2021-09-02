import React, {  useRef, useState, useEffect } from "react";
import styled from "styled-components";
import makeData from "./makeData";
import Table from "../../components/table";
import TableFilter from "../../components/table-filter-form";
import DrawForm from "../../components/form/draw-form";
import SubNaV from "../../components/sub-nav";
import useFetch from 'use-http'
import  { getDSLForm, getFormObjForDraw } from "../../forms_dsl";
import { useParams } from "react-router-dom";
import { Map, List } from "immutable";

const Styles = styled.div``;
function LeadSnapShot(props) {

  const {page, setPage_ } = useState(0);
  const columns = React.useMemo(
    () => [
          {
            Header: "CDB ID",
            accessor: "code",
            canSort:true
          },

          {
            Header: "Client Name",
            accessor: d => `${d.first_name} ${d.last_name}`
          },
          {
            Header: "Email",
            accessor: "email"
          },
          {
            Header: "Contact Number",
            accessor: "mobile_no"
          },
          {
            Header: "Created on",
            accessor: "created_at"
          },
        ],
    []
);

// const getFormObjForDraw = (obj) => {

//     const { form, view_mode_form  } = obj
//     let formObj = forms[form] // Get full form object

//     const { view_mode, mode } = formObj; // From full object getting mode, view_mode and db_table root keys
//     const viewMode = view_mode?.[view_mode_form] // Get the specific form from view_mode to draw
//     if(viewMode) {
//         formObj = {...formObj, sections: viewMode?.sections, model_mode: viewMode?.model_mode}
//     }

//     return formObj
// }


const childRef = useRef();
let paramRef = useRef();
const [view, showView] = useState({table:false, form: false})
const [table, setTable] = useState({columns:[] , data: []})
const [form, setForm] = useState(() => {
    const initialState = getFormObjForDraw({ form: 'policyHolderDetail' , view_mode_form: 'ftc_policy_holder'})
    console.log('-------initialState------')
    console.log(initialState)
    console.log('--------initialState-----')
    return initialState
})
const { get, post,  loading, error } = useFetch()

useEffect(() => {
}, []);

paramRef.current = useParams()
const leftNavList = [
    {
        icon:'fa fa-upload',
        label:'Upload Documents',
        active: 0,
        id:1,
        data:{ form: 'leadAttachment', appendUrl : ''}
    },
//     {
//        icon:'fa fa-file-text-o',
//        label:'FTC Detail',
//        active: 0,
//        id:2,
//        data:{ form: 'ftcDetail'}
//    },
    {
        icon:'fa fa-file-text-o',
        label:'Policy Holder Detail',
        active: 1,
        id:4,
        data:{ form: 'policyHolderDetail' , view_mode_form: 'ftc_policy_holder', appendUrl: `/${paramRef.current.id}`}
    },
   {
       icon:'fa fa-line-chart',
       label:'Vehicle Detail',
       active: 0,
       id:3,
       data:{ form: 'vehicleDetail', appendUrl: `/${paramRef.current.id}`}
   }
]
const doSearch = async (obj) => {

    console.log('--------doSearch----------')
    console.log(obj)
    console.log('--------doSearch----------')
    options.current = { ...options.current, filter:obj }
    const respose = await get(`leads?filter=${JSON.stringify(obj)}`)
    //setData(respose)

}
const onSelect = async (obj) => {
    const { data: { form, view_mode_form, appendUrl } } = obj
    const formObj  = getFormObjForDraw( { form, view_mode_form} )
    const { db_table , view : { columns } } = formObj
    const resp = await get(`form/${db_table}${appendUrl}`)
    console.log('-------obj------')
    console.log(resp)
    console.log(columns)
    console.log('--------obj-----')
    showView({ table : true, form : false })
    setTable({columns:columns, data: resp.data})
    // childRef.current.reRender(formObj, resp?.data)

    //childRef.current.reRender(formObj, [])
}

const saveForm = async (obj) => {

    const resp = await post('form/requestSave',obj)
    console.log('-------saveForm------')
    console.log(obj)
    console.log('-------saveForm------')

}

return (
    <Styles>
      <div className="row x_panel">
            <div className="col-md-2 col-sm-2">
                <SubNaV listNav={leftNavList} onSelect={onSelect} />
            </div>
            <div className="col-md-10 col-sm-10 ">
                <div className="">
                    {/* <div className="x_title"> */}
                        {/* <h2>{formObj.title} <small>{formObj.subtitle}</small></h2> */}
                        {/* <ul className="nav navbar-right panel_toolbox">
                        <li><i className="fa fa-save" style={{paddingTop: 3}}></i>
                            <a style={{display: 'inline'}}>Save</a>
                        </li>
                        </ul> */}
                        {/* <div className="clearfix"></div> */}
                    {/* </div> */}
                    <div className="x_content">
                   { view.form === true &&  <DrawForm options={form} ref={childRef}  onSave={saveForm}/> }
                    </div>
                    { view.table === true && <Table columns={table.columns} data={table.data} /> }
                </div>
            </div>
      </div>
    </Styles>
  );
}
export default LeadSnapShot;
