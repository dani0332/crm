import React, {  useRef, useState } from "react";
import styled from "styled-components";
import makeData from "./makeData";
import Table from "../../components/table";
import TableFilter from "../../components/table-filter-form";
import DrawForm from "../../components/form/draw-form";
import SubNaV from "../../components/sub-nav";
import useFetch from 'use-http'
import * as yup from 'yup';
import * as forms from "../../forms_dsl";


const Styles = styled.div``;
function LeadSnapShot() {

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


const [data, setData] = useState({data:[]})
const [form, setForm] = useState('leadAttachment')
const { get, loading, error } = useFetch()
const refOptions = useRef({});



const leftNavList = [
    {
        icon:'fa fa-upload',
        label:'Upload Documents',
        active: 1,
        id:1,
        data:{ form: 'leadAttachment'}
    },
    {
       icon:'fa fa-file-text-o',
       label:'FTC Detail',
       active: 0,
       id:2,
       data:{ form: 'ftcDetail'}
   },
   {
       icon:'fa fa-line-chart',
       label:'Achievements',
       active: 0,
       id:3,
       data:{ form: 'ftcDetail'}
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
const onSelect = (obj) => {


    console.log(forms)
    const { data: {form} } = obj
    const formObj = forms[form]

    //const objs = forms['leadAttachment']
    //console.log('-------obj------')
    //console.log(forms['leadAttachment'])
     console.log('-------obj------')

     console.log(formObj)
     console.log(obj)
     console.log('-------obj------')

    setForm(form)
}

const formObj = forms[form]

return (
    <Styles>
      <div className="row x_panel">
            <div className="col-md-2 col-sm-2">
                <SubNaV listNav={leftNavList} onSelect={onSelect} />
            </div>
            <div className="col-md-10 col-sm-10 ">
                <div className="">
                    <div className="x_title">
                        <h2>{formObj.title} <small>{formObj.subtitle}</small></h2>
                        <ul className="nav navbar-right panel_toolbox">
                        <li><i className="fa fa-save" style={{paddingTop: 3}}></i>
                            <a style={{display: 'inline'}}>Save</a>
                        </li>
                        </ul>
                        <div className="clearfix"></div>
                    </div>
                    <div className="x_content">
                    <DrawForm options={formObj}  />
                    </div>
                    {/* <Table columns={columns} data={data.data} nextPage={nextPage}  prevPage={prevPage} setPage={setPage}/> */}
                </div>
            </div>
      </div>
    </Styles>
  );
}
export default LeadSnapShot;
