import React from 'react';
import { Menu, MenuItem } from '@szhsin/react-menu';
import '@szhsin/react-menu/dist/index.css';
import '@szhsin/react-menu/dist/transitions/slide.css';
import { confirmAlert } from 'react-confirm-alert';
import { dispatchPromise } from '../sagas';

let globalOpt = {};
const handleResign = () => {
  confirmAlert({
    title: 'Work in Progress',
    message: 'Its in progress',
    buttons: [
      {
        label: 'OK',
        onClick: async () => {},
      },
    ],
  });

  //     React.render(<Dialog title={'title'}  visible>
  //     <p>first dialog</p>
  // </Dialog>, document.getElementsByTagName('body')[0]);
};
const handleUnAssign = () => {
  showConfirm({
    title: 'Un-Assign',
    msg: 'Are you sure to un-assign the code.',
    action: 'unassign',
  });
};

const handleAssignMe = () => {
  showConfirm({
    title: 'Assign Me',
    msg: 'Are you sure to Assign yourself.',
    action: 'assignme',
  });
};

const handleViewQuote = () => {
  globalOpt?.history.push('/lead/' + globalOpt?.row.id);
};

const showConfirm = obj => {
  confirmAlert({
    title: obj?.title,
    message: obj?.msg,
    buttons: [
      {
        label: 'Yes',
        onClick: async () => {
          dispatchPromise({
            dispatch: globalOpt?.dispatch,
            options: {
              type: 'SEND_REQUEST',
              request: {
                url: `/form/teams/${globalOpt.row.id}`,
                method: 'PUT',
                body: JSON.stringify({
                  action: obj?.action,
                  user: globalOpt.row.pa_id,
                }),
              },
            },
          });
          // .then(response => {
          // globalOpt?.dispatch({ type: 'VISIBLE_FORM', formState: 'reset' })
          // })
          // .catch(error => {});
        },
      },
      {
        label: 'No',
        onClick: () => {},
      },
    ],
  });
};

const teams = {
  getForm() {
    const form = {
      db_table: 'teams',
      title: 'Team',
      subtitle: '',
      access: {
        read: ['production_approval_manager'],
        write: ['production_approval_manager'],
        update: ['production_approval_manager'],
        delete: ['production_approval_manager'],
      },
      fields: {
        user_id: {
          type: 'dropdown',
          label: 'Status',
          source: 'users',
          access: {
            read: ['admin', 'production_approval_manager'],
            write: ['admin', 'production_approval_manager'],
            update: ['admin', 'production_approval_manager'],
          },
          rules: { required: true },
          transform(item) {
            if (Array.isArray(item)) {
              const items = item.map(u => {
                return { value: u?.id, label: u?.name };
              });
              return items;
            } else return { value: item?.id, label: item?.name };
          },
        },
      },
      sections: [
        {
          label: 'Teams',
          fields: ['user_id'],
        },
      ],
      view: {
        label: 'FTC History',
        find: {
          basic: [
            {
              type: 'text',
              label: 'CDB ID',
              field: 'code',
            },
            {
              type: 'dropdown',
              label: 'Production Agents',
              source: 'users',
              field: 'user_id',
              transform(item) {
                if (Array.isArray(item)) {
                  const items = item.map(u => {
                    return { value: u?.id, label: u?.name };
                  });
                  return items;
                } else return { value: item?.id, label: item?.name };
              },
            },
          ],
          advanced: [],
        },
        columns: [
          {
            Header: '',
            accessor: 'action',
            Cell: (
              <Menu
                menuButton={
                  <button type='button' className='btn btn-round btn-info'>
                    ...
                  </button>
                }
                transition
              >
                <MenuItem onClick={handleResign}>Re-Assign</MenuItem>
                <MenuItem onClick={handleAssignMe}>Assign Me</MenuItem>
                <MenuItem onClick={handleUnAssign}>Un-Assign</MenuItem>
                <MenuItem onClick={handleViewQuote}>View Quote</MenuItem>
              </Menu>
            ),
          },
          {
            Header: 'CDB ID',
            accessor: 'code',
          },
          {
            Header: 'Status',
            accessor: 'status',
          },
          {
            Header: 'Customer Name',
            accessor: 'customer_name',
          },
          {
            Header: 'Production Agent',
            accessor: 'pa_name',
          },
          {
            Header: 'Production Agent Email',
            accessor: 'pa_email',
          },
        ],
        events: {
          onClick(options) {
            globalOpt = options;
          },
        },
      },
    };
    return form;
  },
};
export default teams;
