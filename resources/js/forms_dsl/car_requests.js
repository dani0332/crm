import { transform } from 'node-json-transform';
import { confirmAlert } from 'react-confirm-alert';
import { dispatchPromise } from '../sagas';
import { vehicleTransform } from '../transforms';
import { session, setLocalStorage, capitalizeFirstLetter } from '../utils';
const leadRequest = {
  getForm() {
    const form = {
      db_table: 'car_quote_request',
      title: 'Lead Listing',
      access: {
        read: [
          'pa',
          'advisor',
          'oe',
          'admin',
          'invoicing',
          'payment',
          'production_approval_manager',
          'production_approval_manager',
        ],
        write: [],
        update: [],
        delete: ['advisor', 'oe', 'admin'],
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
              'oe',
              'admin',
              'invoicing',
              'payment',
              'production_approval_manager',
              'production_approval_manager',
            ],
            write: ['admin'],
            update: ['advisor', 'oe', 'admin'],
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
              'oe',
              'pa',
              'admin',
              'invoicing',
              'payment',
              'production_approval_manager',
              'production_approval_manager',
            ],
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
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
                read: [ 'oe','advisor'],
                write: [],
                update: [],
              },
            },
            {
              type: 'text',
              label: 'Customer email',
              field: 'email',
              access: {
                read: [ 'oe','advisor'],
                write: [],
                update: [],
              },
            },
            {
              type: 'text',
              label: 'Phone number',
              field: 'mobile_no',
              access: {
                read: [ 'oe','advisor'],
                write: [],
                update: [],
              },
            },
            {
              type: 'dropdown',
              label: 'Lead Status',
              field: 'quote_status_id',
              source: 'quote_status',
              access: {
                read: [ 'oe','advisor'],
                write: [],
                update: [],
              },
            },
            {
              type: 'dropdown',
              label: 'Production Agent',
              field: 'pa_id',
              source: 'users',
              filter: { name: 'pa' },
              access: {
                read: [ 'oe','advisor'],
                write: [],
                update: [],
              },
              transform(item) {
                if (Array.isArray(item)) {
                  const items = item.map(u => {
                    return { value: u?.id, label: u?.name };
                  });
                  return items;
                } else return { value: item?.id, label: item?.name };
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
                read: ['pa', 'payment', 'invoicing'],
                write: [],
                update: [],
              },
            },
            {
              type: 'datePicker',
              label: 'Created At',
              field: 'created_at',
              access: {
                read: [ 'oe','advisor'],
                write: [],
                update: [],
              },
              // rules: { required: true }
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
            Header: 'Created on',
            accessor: 'created_at',
          },
          {
            Header: 'Customer Name',
            accessor: d => `${capitalizeFirstLetter(d.first_name)} ${capitalizeFirstLetter(d.last_name)}`,
          },
          {
            Header: 'Lead Status',
            accessor: d => `${d.quote_status_id?.text}`,
          },
          {
            Header: 'Production Agent',
            accessor: d => `${d?.pa_id?.name}`,
          },
          {
            Header: 'Payment Agent',
            accessor: d => `${d?.payment_id?.name}`,
          },
          {
            Header: 'Last Modified',
            accessor: 'updated_at',
          },
        ],
        events: {
          // applyFilter(options){
          //     const generateUrl = `/?filter={"id":-66}`
          //     return generateUrl
          // },
          // applyFilterAfterSearch(options) {
          //   const { filter } = options;
          //   if (Object.keys(filter).length === 0) return { id: -66 };
          //   else return filter;
          // },
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
                title: `${capitalizeFirstLetter(row.first_name)} ${capitalizeFirstLetter(row.last_name)}`,
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
            } else if (role === 'payment' && !row.payment_id) {
              showConfirmMsg();
            }
            else if (role === 'invoicing' && !row.invoicing) {
              showConfirmMsg(); //123
            }
            else {
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
