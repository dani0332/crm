import { transform } from 'node-json-transform';
import { confirmAlert } from 'react-confirm-alert';
import { dispatchPromise } from '../sagas';
import { vehicleTransform } from '../transforms';
import { session, setLocalStorage } from '../utils';
const leadRequest = {
  getForm() {
    const form = {
      db_table: 'car_quote_request',
      title: 'Leads Request',
      access: {
        read: [
          'pa',
          'advisor',
          'admin',
          'invoicing',
          'production_approval_manager',
          'production_approval_manager',
        ],
        write: [],
        update: [],
        delete: ['advisor', 'admin'],
      },
      fields: {
        first_name: {
          type: 'text',
          label: 'First  Name',
          field: 'first_name',
          defaultValue: '',
          rules: { required: true },
          access: {
            read: [
              'pa',
              'advisor',
              'admin',
              'invoicing',
              'production_approval_manager',
              'production_approval_manager',
            ],
            write: ['admin'],
            update: ['advisor', 'admin'],
          },
        },
        last_name: {
          type: 'text',
          label: 'Last Name',
          field: 'last_name',
          defaultValue: '',
          rules: { required: true },
        },
        email: {
          type: 'text',
          label: 'Email',
          field: 'email',
          defaultValue: '',
        },
        mobile_no: {
          type: 'text',
          label: 'Mobile Number',
          field: 'mobile_no',
        },
        car_model_id: {
          type: 'dropdown',
          label: 'Car Model',
          source: 'car_model',
          access: {
            read: [
              'advisor',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
              'production_approval_manager',
            ],
            write: ['advisor', 'admin'],
            update: ['advisor', 'admin'],
          },
          transform(item) {
            if (Array.isArray(item)) {
              const items = item.map(u => {
                return { value: u?.id, label: u?.code };
              });
              return items;
            } else return { value: item?.id, label: item?.code };
          },
        },
        vehicle_detail_subform: {
          type: 'subform',
          form: 'vehicleSubform',
          label: 'Vehicle Information',
        },
      },
      sections: [
        {
          label: 'Leads Information',
          fields: [
            'first_name',
            'car_model_id',
            'last_name',
            'email',
            'vehicle_detail_subform',
            'mobile_no',
          ],
        },
      ],
      view: {
        find: {
          basic: [
            {
              type: 'text',
              label: 'CDB ID',
              field: 'code',
              access: {
                read: ['advisor'],
                write: [],
                update: [],
              },
            },
            {
              type: 'dropdown',
              label: 'Lead List',
              field: 'pa_id',
              source: [
                { id: 0, text: 'Un Assigned Leads' },
                { id: 1, text: 'Assigned Leads' },
              ],
              access: {
                read: ['pa', 'invoicing', 'production_approval_manager'],
                write: [],
                update: [],
              },
            },
          ],
          advanced: [],
        },
        columns: [
          {
            Header: 'CDB ID',
            accessor: 'code',
          },
          {
            Header: 'Client Name',
            accessor: d => `${d.first_name} ${d.last_name}`,
          },
          {
            Header: 'Created on',
            accessor: 'created_at',
          },
        ],
        events: {
          // applyFilter(options){
          //     const generateUrl = `/?filter={"id":-66}`
          //     return generateUrl
          // },
          applyFilterAfterSearch(options) {
            const { filter } = options;
            if (Object.keys(filter).length === 0) return { id: -66 };
            else return filter;
          },
          afterFetchData(options) {
            const { resp } = options;
            return resp.data;
          },
          onClick(options) {
            const { role } = session();
            const { row, dispatch, history } = options;
            setLocalStorage('car_request_snap', row);
            console.log(row);

            const showConfirmMsg = () => {
              confirmAlert({
                title: `${row.first_name} ${row.last_name}`,
                message: 'Are you sure you want to assign this lead yourself?',
                buttons: [
                  {
                    label: 'Yes',
                    onClick: async () => {
                      dispatchPromise({
                        dispatch: dispatch,
                        options: {
                          type: 'SEND_REQUEST',
                          request: {
                            url: `/form/car_quote_request/${row.id}`,
                            method: 'PUT',
                            body: JSON.stringify({ action: 'assign' }),
                          },
                        },
                      })
                        .then(() => {
                          dispatch({
                            type: 'VISIBLE_FORM',
                            formState: 'reset',
                          });
                          history.push('/lead/' + row.id);
                        })
                        .catch(error => {
                          console.log(error);
                        });
                    },
                  },
                  {
                    label: 'No',
                    onClick: () => {},
                  },
                ],
              });
            };
            if (role === 'pa' && !row.pa_id) {
              showConfirmMsg();
            } else if (role === 'invoicing' && !row.invoicing) {
              showConfirmMsg();
            } else {
              dispatch({ type: 'VISIBLE_FORM', formState: 'reset' });
              history.push('/lead/' + row.id);
            }
          },
        },
      },
      postTransform(options) {
        const { data } = options;
        var result = transform(data, vehicleTransform);
        console.log(result);
        return result;
      },
    };
    return form;
  },
};
export default leadRequest;
