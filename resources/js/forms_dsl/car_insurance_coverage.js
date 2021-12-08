let insuranceDetail = {
  getForm() {
    const yuu = {
      db_table: 'car_quote_insurance_coverage',
      mode: 'insurance_coverage_detail',
      title: 'Insurance Coverage Information',
      subtitle: '',
      access: {
        read: [
          'pa',
          'advisor',
          'oe',
          'admin',
          'invoicing',
          'production_approval_manager',
        ],
        write: ['advisor', 'admin', 'oe'],
        update: ['advisor', 'admin', 'oe'],
        delete: [],
      },
      fields: {
        start_date: {
          type: 'datePicker',
          label: 'Policy start date',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor','oe', 'admin'],
          },
          // rules: { required: true }
        },
        insurance_company_id: {
          type: 'dropdown',
          label: 'Insurance Company',
          source: 'insurance_companies',
          rules: { required: true },
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor','oe', 'admin'],
            update: ['advisor','oe', 'admin'],
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
        insurance_plan_id: {
          type: 'dropdown',
          label: 'Insurance Plan',
          source: 'car_quote_insurance_plan',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
          },
          rules: { required: true },
        },
        vehicle_type_id: {
          type: 'dropdown',
          label: 'Vehicle Type',
          source: 'vehicle_type',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor',  'oe','admin'],
            update: ['advisor',  'oe','admin'],
          },
          rules: { required: true },
        },
        excess: {
          type: 'text',
          label: 'Excess',
          access: {
            read: [
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'pa',
              'production_approval_manager',
            ],
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
          },
          rules: { required: true },
        },
        sum_insured: {
          type: 'text',
          label: 'Sum Insured',
          access: {
            read: [
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'pa',
              'production_approval_manager',
            ],
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
          },
          rules: { required: true },
        },
        premium_price: {
          type: 'text',
          label: 'Premium/Price',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
          },
          rules: { required: true },
        },
        ancillary_excess: {
          type: 'text',
          label: 'Ancillary Excess',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
          },
          rules: { required: true },
        },
        personal_accident_benefit: {
          type: 'dropdown',
          label: 'Personal Accident Benefit',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
          },
          source: ['INCLUDED', 'NOT INCLUDED'],
        },
        breakdown_recovery: {
          type: 'dropdown',
          label: 'Breakdown recovery',
          source: ['INCLUDED', 'NOT INCLUDED'],
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
          },
          rules: { required: true },
        },
        off_road_cover: {
          type: 'dropdown',
          label: 'Off-road cover (for 4X4 only)',
          source: ['INCLUDED', 'NOT INCLUDED'],
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor',  'oe','admin'],
            update: ['advisor', 'oe', 'admin'],
          },
          rules: { required: true },
        },
        rend_a_car: {
          type: 'dropdown',
          label: 'Rent a Car',
          source: ['INCLUDED', 'NOT INCLUDED'],
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor',  'oe','admin'],
            update: ['advisor', 'oe', 'admin'],
          },
          rules: { required: true },
        },
        repair_type: {
          type: 'dropdown',
          label: 'Repair Type',
          source: ['Agency', 'NON Agency', 'Not Applicable'],
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor',  'oe','admin'],
            update: ['advisor', 'oe', 'admin'],
          },
          rules: { required: true },
        },
        financed_by: {
          type: 'text',
          label: 'Financed By',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
          },
          rules: { required: true },
        },
        geographical_area: {
          type: 'text',
          label: 'Geographical Area',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
          },
          rules: { required: true },
        },
      },
      sections: [
        {
          label: 'Insurance Coverage Information',
          //fields: ['start_date', 'insurance_company_id']
          fields: [
            'start_date',
            'insurance_company_id',
            'insurance_plan_id',
            'vehicle_type_id',
            'excess',
            'sum_insured',
            'premium_price',
            'ancillary_excess',
            'personal_accident_benefit',
            'breakdown_recovery',
            'off_road_cover',
            'rend_a_car',
            'repair_type',
            'financed_by',
            'geographical_area',
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

            if(form?.data.length < 1)
              return form

            const code  = form?.data?.car_quote_id?.quote_status_id?.code;
            if(code && code === 'ftc_pending' || code === 'ftc_accepted') {
              return form
            }

            const access = {
              read: [
                'pa',
                'advisor',
                'oe',
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
          transformBeforeOpenEditMode(form) {
            console.log('-------------transformBeforeOpenEditMode---------------')
            console.log(form)
            console.log('-------------transformBeforeOpenEditMode---------------')
            // if(form?.getForm?.context === 'car_quote_snap' && form?.formState === 'list' && form?.getForm?.multi === false) {
            //     return { ...data, mode_id: { id: data?.mode_id?.id, text: data?.mode_id?.name}}
            // }
            // return data
          },
          applyFilter(options) {
            const {
              params: { id },
            } = options;
            const generateUrl = `/?filter={"car_quote_id":"${id}"}`;
            return generateUrl;
          },
          afterFetchData(options) {
            const { resp, dispatch } = options;
            dispatch({
              type: 'VISIBLE_FORM',
              object: resp.data[0],
              formState: 'read',
            });
            return resp.data;
          },
        },
      },
      postTransform(options) {
        const { params, data } = options;
        return { ...data, car_quote_id: params.id };
      },
    };
    return yuu;
  },
};

export default { ...insuranceDetail };
