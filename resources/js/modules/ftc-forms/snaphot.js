import React, {  useRef, useState, useEffect } from "react";
import styled from "styled-components";
import makeData from "./makeData";
import Table from "../../components/table";
import TableFilter from "../../components/table-filter-form";
import DrawForm from "../../components/form/draw-form";
import SubNaV from "../../components/sub-nav";
import useFetch from 'use-http'
import * as forms from "../../forms_dsl";
import { useParams } from "react-router-dom";

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

// const defaultForm = {
//     fields: [
//         {
//             type:'text',
//             label:'CDB ID',
//             field:'code'
//         },
//         {
//             type:'text',
//             label:'Email',
//             field:'email'
//         },
//         {
//             type:'file',
//             label:'documents',
//             field:'document'
//         },
//         {
//             type:'dropdown',
//             label:'Source Array',
//             field:'source',
//             // sourcefilter:
// 			// 	role:
// 			// 		'static': 'pharm'
// 			// 	group_role:
// 			// 		'static': '!tech'
//            // source: 'leads',
//             //template: ` `
//             source: [{ id: 1, title: 'One' }, { id: 2, title: 'Two' }]
//             //source: ["One", "Two", "Three"]
//         }

//     ],
//     schema: yup.object().shape({
//         code: yup.string().required().min(4),
//         email: yup.string().email()
//       })
// };

const childRef = useRef();
let paramRef = useRef();

const [data, setData] = useState({data:[]})
const [form, setForm] = useState('leadAttachment')
const { get, post,  loading, error } = useFetch()


useEffect(() => {
  }, []);

paramRef.current = useParams()
const leftNavList = [
    {
        icon:'fa fa-upload',
        label:'Upload Documents',
        active: 1,
        id:1,
        data:{ form: 'leadAttachment'}
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
        active: 0,
        id:4,
        data:{ form: 'policyHolderDetail'}
    },
   {
       icon:'fa fa-line-chart',
       label:'Vehicle Detail',
       active: 0,
       id:3,
       data:{ form: 'vehicleDetail'}
   }
]
const doSearch = async (obj) => {

    console.log('--------doSearch----------')
    console.log(obj)
    console.log('--------doSearch----------')
    options.current = { ...options.current, filter:obj }
    const respose = await get(`leads?filter=${JSON.stringify(obj)}`)
    setData(respose)

}
const onSelect = async (obj) => {
    const { data: {form} } = obj
    const formObj = forms[form]
    console.log('-------obj------')
    console.log(formObj)
    const { db_table, mode } = formObj;
    const resp = await get(`form/${db_table}/${paramRef.current.id}?mode=${mode}`)
    console.log('-------obj------')
    console.log(resp)
    console.log('--------obj-----')
    childRef.current.reRender(formObj,resp?.data)
}

const saveForm = async (obj) => {

    const resp = await post('form/requestSave',obj)
    console.log('-------saveForm------')
    console.log(obj)
    console.log('-------saveForm------')

}

const formObj = forms[form]

console.log('-------formObj------')
console.log(formObj)
console.log('-------formObj------')

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
                    <DrawForm options={formObj} ref={childRef}  onSave={saveForm}/>
                    </div>
                    {/* <Table columns={columns} data={data.data} nextPage={nextPage}  prevPage={prevPage} setPage={setPage}/> */}
                </div>
            </div>
      </div>
    </Styles>
  );
}
export default LeadSnapShot;
