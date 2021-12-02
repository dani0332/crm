import { vehicleTransform } from '../transforms';
var transform = require('node-json-transform').transform;

let vehicleDetail = {
  getForm() {
    const yuu = {
      db_table: 'car_quote_request',
      title: 'Car Quote Request',
      subtitle: '',
      access: {
        read: [
          'advisor',
          'pa',
          'admin',
          'invoicing',
          'production_approval_manager',
        ],
        write: ['advisor', 'admin'],
        update: ['advisor', 'admin'],
        delete: [],
      },
      fields: {
        Year_of_manufacture: {
          type: 'text',
          label: 'Year of Manufacture',
          field: 'Year_of_manufacture',
          rules: { required: true },
          access: {
            read: [
              'advisor',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: [],
            update: [],
          },
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
            ],
            write: [],
            update: [],
          },
        },
        car_make_id: {
          type: 'dropdown',
          label: 'Car Make',
          source: 'car_make',
          access: {
            read: [
              'advisor',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: [],
            update: [],
          }
        },
        emirate_of_registration_id: {
          type: 'dropdown',
          label: 'Emirate of Registration',
          access: {
            read: [
              'advisor',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: [],
            update: [],
          },
          transform(item) {
            return { value: item?.id, label: item?.text };
          },
        },
        uae_license_held_for_id: {
          type: 'dropdown',
          label: 'UAE licence held for',
          access: {
            read: [
              'advisor',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: [],
            update: [],
          },
          transform(item) {
            return { value: item?.id, label: item?.text };
          },
        },
        claim_history_id: {
          type: 'dropdown',
          label: 'Claim',
          form: 'claim_history',
          access: {
            read: [
              'advisor',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: [],
            update: [],
          },
        },
        vehicle_detail_id: {
          type: 'subform',
          form: 'vehicleSubform',
          label: 'Vehicle Information',
        },
      },
      sections: [
        {
          label: 'Car Quote Detail',
          fields: [
            'Year_of_manufacture',
            'car_model_id',
            'car_make_id',
            'emirate_of_registration_id',
            'uae_license_held_for_id',
            'claim_history_id',
            'vehicle_detail_id',
          ],
        },
      ],
      view: {
        find: {
          basic: [],
          advanced: [],
        },
        columns: [
          {
            Header: 'Manufacture Year',
            accessor: 'year_of_manufacture',
          },
        ],
        events: {

          transformBeforeOpenReadMode(form){

            const code  = form?.data?.quote_status_id?.code;
            if(code && code === 'ftc_pending' || code === 'ftc_accepted') {
              return form
            }
            
            const access = {
              read: [
                'pa',
                'advisor',
                'admin',
                'invoicing',
                'production_approval_manager',
              ],
              write: [],
              update: [],
              delete: [],
            };

            form.access = access
            return form
          
        },
          applyFilter(options) {
            const {
              params: { id },
            } = options;
            const generateUrl = `/${id}`;
            return generateUrl;
          },
          afterFetchData(options) {
            const { resp, dispatch } = options;
            dispatch({
              type: 'VISIBLE_FORM',
              object: resp.data,
              formState: 'read',
            });
            return resp.data;
          },
        },
      },
      postTransform(options) {
        const { params } = options;
        // if(state?.context === 'car_quote_snap'){
        //     return { ...data, car_quote_id: id}
        // }
        console.log('-----postTransform-----');
        console.log(params);
        let { data } = options;
        data.car_quote_id = params.id;
        data.form = 'vehicle_detail_for_car_quote';
        var result = transform(data, vehicleTransform);
        console.log(result);
        return result;
      },
    };
    return yuu;
  },
};

export default { ...vehicleDetail };
