import React, {  useEffect, useState } from "react";
import styled from "styled-components";
import makeData from "./makeData";
import Table from "../../components/table";
import TableFilter from "../../components/table-filter-form";

const Styles = styled.div` padding: 1rem;`;

function LeadsList() {
  const columns = React.useMemo(
    () => [
      {
        Header: "Name",
        columns: [
          {
            Header: "First Name",
            accessor: "firstName"
          },
          {
            Header: "Last Name",
            accessor: "lastName"
          }
        ]
      },
      {
        Header: "Info",
        columns: [
          {
            Header: "Age",
            accessor: "age",
          },
          {
            Header: "Visits",
            accessor: "visits"
          },
          {
            Header: "Status",
            accessor: "status"
          },
          {
            Header: "Profile Progress",
            accessor: "progress"
          }
        ]
      }
    ],
    []
);

const options = {
    url:'',
    filter: [
        {
            type:'text',
            label:'Title',
            field:'title',
            validation:{}
        },
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
            type: 'dropdown',
            label:'Month of Year',
            field:'year',
            data: [{ title: ' First Value', id: 1 }, { title: 'Second Value',  id: 2 }],
            validation:{}
        }
    ]
};
const [data, setData] = useState([]);
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
                    <TableFilter filter={options.filter} url={options.url} />
                    </div>
                    <Table columns={columns} data={data}  />
                </div>
            </div>
      </div>
    </Styles>
  );
}

export default LeadsList;
