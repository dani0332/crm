import React from 'react';
import { useTable, useSortBy } from 'react-table';
import { useHistory } from 'react-router-dom';
import { useDispatch } from 'react-redux';
import FormContext from './form/FormContext';

function Table({ columns, data, nextPage, prevPage, view, form }) {
  // Use the state and functions returned from useTable to build your UI

  const dispatch = useDispatch();
  const Context = React.useContext(FormContext);
  const { manageListDispatch, initialForm } = Context;

  const history = useHistory();
  const { getTableProps, getTableBodyProps, headerGroups, rows, prepareRow } =
    useTable(
      {
        columns,
        data,
      },
      useSortBy,
    );

  // Render the UI for your table

  return (
    <>
      <div className='table-responsive'>
        <table
          style={{ marginTop: 30 }}
          {...getTableProps()}
          className='table table-striped  '
        >
          <thead>
            {headerGroups.map((headerGroup, i) => (
              <tr
                {...headerGroup.getHeaderGroupProps()}
                className='headings'
                key={i}
              >
                {headerGroup.headers.map((column, i) => (
                  <th {...column.getHeaderProps()} key={i}>
                    {column.render('Header')}
                    {/* Add a sort direction indicator */}
                    <span>
                      {column.isSorted
                        ? column.isSortedDesc
                          ? ' 🔽'
                          : ' 🔼'
                        : ''}
                    </span>
                  </th>
                ))}
              </tr>
            ))}
          </thead>
          <tbody {...getTableBodyProps()}>
            {rows.map((row, i) => {
              prepareRow(row);
              return (
                <tr
                  key={i}
                  style={{ cursor: 'pointer' }}
                  {...row.getRowProps()}
                  onClick={() => {
                    const { events } = view;
                    if (events && typeof events.onClick == 'function')
                      events.onClick({
                        row: row.original,
                        dispatch: dispatch,
                        history: history,
                        initialForm: initialForm,
                        selectedRecord: row.original,
                        form: form,
                        formState: 'read',
                        manageListDispatch: manageListDispatch,
                      });
                    else
                      dispatch({
                        type: 'VISIBLE_FORM',
                        initialForm: initialForm,
                        selectedRecord: row.original,
                        form: form,
                        formState: 'read',
                        manageListDispatch: manageListDispatch,
                      });
                  }}
                >
                  {row.cells.map((cell, i) => {
                    return (
                      <td {...cell.getCellProps()} key={i}>
                        {cell.render('Cell')}
                      </td>
                    );
                  })}
                </tr>
              );
            })}

            {rows.length < 1 && (
              <tr>
                <td colSpan={columns.length} align='center'>
                  <p>No Data found.</p>
                </td>
              </tr>
            )}
          </tbody>
        </table>

        <div className='dataTables_paginate paging_simple_numbers'>
          <ul className='pagination'>
            <li
              className='paginate_button previous'
              style={{ paddingRight: 2 }}
            >
              <a
                href='javascript:void(0)'
                onClick={prevPage}
                style={{ borderRadius: 7 }}
              >
                Previous
              </a>
            </li>
            <li>
              <input
                className='form-control'
                style={{ width: 80, marginTop: -8 }}
                type='number'
              />
            </li>
            <li className='paginate_button next' style={{ paddingLeft: 2 }}>
              <a
                href='javascript:void(0)'
                onClick={nextPage}
                style={{ borderRadius: 7 }}
              >
                Next
              </a>
            </li>
          </ul>
        </div>
      </div>
    </>
  );
}

export default Table;
