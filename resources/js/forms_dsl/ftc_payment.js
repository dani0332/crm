import { session, setLocalStorage, capitalizeFirstLetter } from '../utils';

const ftcPayment = {
  getForm() {
    const form = {
      db_table: 'car_quote_payment',
      title: 'FTC Payment',
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
        write: ['advisor', 'oe', 'admin'],
        update: ['advisor', 'oe', 'admin'],
        delete: [],
      },
      fields: {
        mode_id: {
          type: 'dropdown',
          label: 'Payment Mode',
          source: [{ id : 24, text: "Credit Card Payment"}, { id: 25 ,text: "Non-credit Card Payment"}],
          if: {
            24: {
              fields: ['method'],
            },
          },
          else: ['method'],
          field: 'mode_id',
          // transform(item, condition = false) {
          //   if (condition)
          //     return {
          //       value: { id: item?.value, name: item?.label },
          //       selected: item?.label,
          //     };
          //   if (Array.isArray(item)) {
          //     const items = item.map(u => {
          //       return { value: u?.id, label: u?.name };
          //     });
          //     return items;
          //   } else
          //     return {
          //       value: item?.id,
          //       label: item?.name,
          //       selected: item?.name,
          //     };
          // },
          access: {
            read: [
              'advisor',
              'pa',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: [ 'oe','advisor'],
            update: [ 'oe','advisor'],
          },
          rules: { required: true },
        },
        method: {
          type: 'dropdown',
          label: 'Payment Method',
          source: ['Spotii', 'payments.insurancemarket.ae '],
          offscreen: true,
          access: {
            read: [
              'advisor',
              'oe',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: [ 'oe','advisor'],
            update: [ 'oe','advisor'],
          },
          rules: { required: true },
        },
        comment: {
          type: 'textarea',
          label: 'Comment',
          access: {
            read: [
              'advisor',
              'oe',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: [ 'oe','advisor'],
            update: [ 'oe','advisor'],
          },
          rules: {},
        },
      },
      sections: [
        {
          label: 'FTC Payment Method',
          fields: ['mode_id', 'method', 'comment'],
        },
      ],
      view: {
        label: 'FTC Payment Method',
        find: {
          basic: [],
          advanced: [],
        },
        columns: [
          {
            Header: 'ID',
            accessor: 'id',
          },
        ],
        events: {
          transformBeforeOpenReadMode(form){
            if(form?.data.length < 1)
              return form

            const code  = form?.data?.car_quote_id?.quote_status_id?.code;
            const { role } = session();
            if(code && code === 'transaction_declined' && role === 'invoicing' ) {
              const access = {
                read: [
                  'pa',
                  'advisor',
                  'oe',
                  'admin',
                  'invoicing',
                  'production_approval_manager',
                ],
                write: ['invoicing'],
                update: ['invoicing'],
                delete: [],
              };
              form.access = access
              return form
            }
            return form
          },

          transformAfterFetchFromServer(form, data){
              if(form?.getForm?.context === 'car_quote_snap' && form?.formState === 'list' && form?.getForm?.multi === false) {
                  return { ...data, mode_id: { id: data?.mode_id?.id, text: data?.mode_id?.name}}
              }
              return data
          },
          applyFilter(options) {
            const {
              params: { id },
            } = options;
            const generateUrl = `/?filter={"car_quote_id":"${id}"}`;
            return generateUrl;
          },
        },
      },
      postTransform(options) {
        const { params, data } = options;
        console.log({ params, data })
        return { ...data, car_quote_id: params.id };
      },
    };
    return form;
  },
};
export default ftcPayment;
