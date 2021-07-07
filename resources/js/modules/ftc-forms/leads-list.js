import React, {  useRef, useState } from "react";
import styled from "styled-components";
import makeData from "./makeData";
import Table from "../../components/table";
import TableFilter from "../../components/table-filter-form";
import useFetch from 'use-http'

const Styles = styled.div` padding: 1rem;`;

function LeadsList() {

  const {page, setPage_ } = useState(0);
  const columns = React.useMemo(
    () => [
          {
            Header: "COB ID",
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


const { get, loading, error, data = { data:[] } } = useFetch('http://127.0.0.1:8000/leads', options, [])
const refOptions = useRef({});

const nextPage = async () => {

    console.log('-age')
    await get(`?page=${page}`)
    refOptions.current = {...refOptions.current, page:page + 1}
}

const prevPage = () => {
    console.log('--------data prev----------')

}

const setPage = (page) => {
    console.log('--------data prev----------')

}

const options = {
    url:'',
    filter: [
        {
            type:'text',
            label:'First Name',
            field:'first_name',
            validation:{}
        },
        {
            type:'text',
            label:'Last Name',
            field:'last_name',
            validation:{}
        },
        {
            type:'text',
            label:'COB ID',
            field:'code',
            validation:{}
        },
        {
            type:'text',
            label:'Contact Number',
            field:'mobile_no',
            validation:{}
        },
        {
            type:'text',
            label:'Email',
            field:'email',
            validation:{}
        },
        // {
        //     type: 'dropdown',
        //     label:'Month of Year',
        //     field:'year',
        //     data: [{ title: ' First Value', id: 1 }, { title: 'Second Value',  id: 2 }],
         //      'api':'red'
        //     validation:{}
        // }
    ]
};


console.log('--------data----------')
console.log(data)
console.log('--------data----------')
const doSearch = async (obj) => {
    console.log('--------doSearch----------')
    console.log(obj)
    console.log('--------doSearch----------')
    options.current = {...options.current, filter:obj}
    await get(`?filter=${JSON.stringify(obj)}`)

}

return (
    <Styles>
      <div className="row">
            <div className="col-md-12 col-sm-12 ">
                <div className="x_panel">
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
                    <TableFilter filter={options.filter} url={options.url} search={doSearch} />
                    </div>
                    <Table columns={columns} data={data.data} nextPage={nextPage}  prevPage={prevPage} setPage={setPage}/>
                </div>
            </div>
      </div>
    </Styles>
  );
}

export default LeadsList;
