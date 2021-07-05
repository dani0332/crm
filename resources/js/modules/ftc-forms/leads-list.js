import React, {  useEffect, useState } from "react";
import styled from "styled-components";
import makeData from "./makeData";
import Table from "../../components/table";
import TableFilter from "../../components/table-filter-form";
import useFetch from 'use-http'

const Styles = styled.div` padding: 1rem;`;

function LeadsList() {
  const columns = React.useMemo(
    () => [
          {
            Header: "Coupon Code",
            accessor: "coupon_code",
            canSort:true
          },
          {
            Header: "Active",
            accessor: "is_active"
          },
          {
            Header: "Discount",
            accessor: "discount"
          }
        ],
    []
);

const { get, loading, error, data = { data:[] } } = useFetch('http://127.0.0.1:8000/leads', options, [])
const options = {
    url:'',
    filter: [
        {
            type:'text',
            label:'Coupon Code',
            field:'coupon_code',
            validation:{}
        },
        // {
        //     type:'text',
        //     label:'First Name',
        //     field:'first_name',
        //     validation:{}
        // },
        // {
        //     type:'text',
        //     label:'Last Name',
        //     field:'last_name',
        //     validation:{}
        // },
        // {
        //     type: 'dropdown',
        //     label:'Month of Year',
        //     field:'year',
        //     data: [{ title: ' First Value', id: 1 }, { title: 'Second Value',  id: 2 }],
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
    await get(`?filter=${JSON.stringify(obj)}`)

}

return (
    <Styles>
      <div className="row">
            <div className="col-md-12 col-sm-12 ">
                <div className="x_panel">
                    <div className="x_title">
                        <h2>Leads <small>Information</small></h2>
                        <div className="clearfix"></div>
                    </div>
                    <div className="x_content">
                    <TableFilter filter={options.filter} url={options.url} search={doSearch} />
                    </div>
                    <Table columns={columns} data={data.data}  />
                </div>
            </div>
      </div>
    </Styles>
  );
}

export default LeadsList;
