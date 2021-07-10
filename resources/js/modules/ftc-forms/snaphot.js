import React, {  useRef, useState } from "react";
import styled from "styled-components";
import makeData from "./makeData";
import Table from "../../components/table";
import TableFilter from "../../components/table-filter-form";
import DrawForm from "../../components/form/draw-form";
import SubNaV from "../../components/sub-nav";
import useFetch from 'use-http'
import * as yup from 'yup';


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

const [data, setData] = useState({data:[]})
const { get, loading, error } = useFetch()
const refOptions = useRef({});

const form = {
    fields: [
        {
            type:'text',
            label:'CDB ID',
            field:'code'
        },
        {
            type:'text',
            label:'Email',
            field:'email'
        },
        {
            type:'file',
            label:'documents',
            field:'document'
        },
        {
            type:'dropdown',
            label:'Source Array',
            field:'source',
           // source: 'leads',
            //template: ` `
            //source: [{ id: 1, title: 'One' }, { id: 2, title: 'Two' }]
            source: ["One", "Two", "Three"]
        }

    ],
    schema: yup.object().shape({
        code: yup.string().required().min(4),
        email: yup.string().email()
      })
};

const leftNavList = [
    {
        icon:'fa fa-calendar',
        label:'Upload Documents',
        active: 0,
        id:1,
        data:{}
    },
    {
       icon:'fa fa-bar-chart',
       label:'Auto Renewal',
       active: 1,
       id:2,
       data:{}
   },
   {
       icon:'fa fa-line-chart',
       label:'Achievements',
       active: 0,
       id:3,
       data:{}
   },
   {
       icon:'fa fa-calendar',
       label:'Achievements',
       active: 0,
       id:4,
       data:{}
   },
   {
       icon:'fa fa-calendar',
       label:'Setting',
       active: 0,
       id:5,
       data:{}
   },
   {
       icon:'fa fa-line-chart',
       label:'Setting',
       active: 0,
       id:6,
       data:{}
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

    console.log('-------obj------')
    console.log(obj)
    console.log('-------obj------')
}

return (
    <Styles>
      <div className="row x_panel">
            <div className="col-md-2 col-sm-2">
                <SubNaV listNav={leftNavList} onSelect={onSelect} />
            </div>
            <div className="col-md-10 col-sm-10 ">
                <div className="">
                    <div className="x_title">
                        <h2>Leads <small>Information</small></h2>
                        <ul className="nav navbar-right panel_toolbox">
                        <li><i className="fa fa-plus" style={{paddingTop: 3}}></i>
                            <a style={{display: 'inline'}}>New Lead</a>
                        </li>
                        </ul>
                        <div className="clearfix"></div>
                    </div>
                    <div className="x_content">
                    <DrawForm options={form}  />
                    </div>
                    {/* <Table columns={columns} data={data.data} nextPage={nextPage}  prevPage={prevPage} setPage={setPage}/> */}
                </div>
            </div>
      </div>
    </Styles>
  );
}
export default LeadSnapShot;
