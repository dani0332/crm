import React, { useEffect, useState } from "react";
import styled from "styled-components";
import { useFilters, useTable } from "react-table";

const Styles = styled.div`
padding: 1rem;
`;

function Table({ columns, data, ageOutside }) {
  // Use the state and functions returned from useTable to build your UI
  const {
    getTableProps,
    getTableBodyProps,
    headerGroups,
    rows,
    prepareRow,
    setFilter
  } = useTable(
    {
      columns,
      data
    },
    useFilters
  );

  // Listen for input changes outside
  useEffect(() => {
    // This will now use our custom filter for age
    setFilter("age", ageOutside);
  }, [ageOutside]);

  // Render the UI for your table
  return (
    <>
      <div className="table-responsive">
      <table style={{ marginTop: 30 }} {...getTableProps()} className="table table-striped jambo_table bulk_action">
        <thead>
          {headerGroups.map((headerGroup) => (
            <tr {...headerGroup.getHeaderGroupProps()} className="headings">
              {headerGroup.headers.map((column) => (
                <th {...column.getHeaderProps()}>{column.render("Header")}</th>
              ))}
            </tr>
          ))}
        </thead>
        <tbody {...getTableBodyProps()}>
          {rows.map((row, i) => {
            prepareRow(row);
            return (
              <tr {...row.getRowProps()}>
                {row.cells.map((cell) => {
                  return (
                    <td {...cell.getCellProps()}>{cell.render("Cell")}</td>
                  );
                })}
              </tr>
            );
          })}
        </tbody>
      </table>
      </div>
    </>
  );
}

export default Table
