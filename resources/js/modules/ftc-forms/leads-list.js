import React, {  useRef, useState } from "react";
import styled from "styled-components";
import makeData from "./makeData";
import Table from "../../components/table";
import TableFilter from "../../components/table-filter-form";
import SubNaV from "../../components/sub-nav";
import useFetch from 'use-http'


const Styles = styled.div``;

function LeadsList() {

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
            label:'CDB ID',
            field:'code',
            validation:{}
        },
        {
            type:'text',
            label:'Email',
            field:'email',
            validation:{}
        }
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
    const respose = await get(`leads?filter=${JSON.stringify(obj)}`)
    setData(respose)

}

return (
    <Styles>
      <div className="row x_panel">
            <div className="col-md-2 col-sm-2">
                <SubNaV />
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
