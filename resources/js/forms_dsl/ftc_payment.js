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
          'admin',
          'invoicing',
          'production_approval_manager',
        ],
        write: ['advisor', 'admin'],
        update: ['advisor', 'admin'],
        delete: [],
      },
      fields: {
        mode_id: {
          type: 'dropdown',
          label: 'Payment Mode',
          source: 'payment_modes',
          if: {
            CC: {
              fields: ['method'],
            },
          },
          else: ['method'],
          field: 'mode_id',
          transform(item, condition = false) {
            if (condition)
              return {
                value: { id: item?.value, name: item?.label },
                selected: item?.label,
              };
            if (Array.isArray(item)) {
              const items = item.map(u => {
                return { value: u?.id, label: u?.name };
              });
              return items;
            } else
              return {
                value: item?.id,
                label: item?.name,
                selected: item?.name,
              };
          },
          access: {
            read: [
              'advisor',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor'],
            update: ['advisor'],
          },
          rules: { required: true },
        },
        method: {
          type: 'dropdown',
          label: 'Payment Method',
          source: ['Spotii', 'payments.insurancemarket.ae '],
          field: 'method',
          offscreen: true,
          access: {
            read: [
              'advisor',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor'],
            update: ['advisor'],
          },
          rules: { required: true },
        },
        comment: {
          type: 'textarea',
          label: 'Comment',
          access: {
            read: [
              'advisor',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor'],
            update: ['advisor'],
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
        return { ...data, car_quote_id: params.id };
      },
    };
    return form;
  },
};
export default ftcPayment;
