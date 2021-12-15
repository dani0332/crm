const carQuotePolicy = {
  getForm() {
    const form = {
      db_table: 'car_quote_policy',
      title: 'Car Quote Policy',
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
        write: ['pa', 'admin'],
        update: ['pa', 'admin'],
        delete: [],
      },
      fields: {
        customer: {
          type: 'text',
          label: 'Customer',
          access: {
            read: [
              'advisor',
              'oe',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
          },
          transform(item) {
            return `${item?.first_name}  ${item?.last_name}`;
          },
        },
        quote_number: {
          type: 'text',
          label: 'Quote Number',
          access: {
            read: [
              'advisor',
              'oe',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['pa'],
            update: ['pa'],
          },
          rules: { required: true },
        },
        policy_number: {
          type: 'text',
          label: 'Policy Number',
          access: {
            read: [
              'advisor',
              'oe',
              'pa',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['pa'],
            update: ['pa'],
          },
          rules: { required: true },
        },
        issue_date: {
          type: 'datePicker',
          label: 'Policy Issue Date',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['pa', 'admin'],
            update: ['pa', 'admin'],
          },
          // rules: { required: true }
        },
        start_date: {
          type: 'datePicker',
          label: 'Policy Start Date',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['pa', 'admin'],
            update: ['pa', 'admin'],
          },
        },
        end_date: {
          type: 'datePicker',
          label: 'Policy End Date',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['pa', 'admin'],
            update: ['pa', 'admin'],
          },
        },
        approval_code: {
          type: 'text',
          label: 'Approval Code',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
          },
        },
        amount_paid: {
          type: 'text',
          label: 'Amound Paid',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
          },
        },
        payment_mode_id: {
          type: 'dropdown',
          label: 'Payment Mode',
          source: 'payment_modes',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
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
        typeofinsurance: {
          type: 'dropdown',
          label: 'Type of Insurance',
          source: 'type_of_insurances',
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
          },
        },
        insurance_company_id: {
          type: 'dropdown',
          label: 'Insurance Company',
          source: 'insurance_provider',
          filter: {insurance_company_id: { op: "<>", val: "null"}},
          access: {
            read: [
              'pa',
              'advisor',
              'oe',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
          },
          // transform(item) {
          //   if (Array.isArray(item)) {
          //     const items = item.map(u => {
          //       return { value: u?.id, label: u?.name };
          //     });
          //     return items;
          //   } else return { value: item?.id, label: item?.name };
          // },
        },
      },
      sections: [
        {
          label: 'Car Quote Policy Record',
          fields: [
            'customer',
            'approval_code',
            'amount_paid',
            'payment_mode_id',
            'typeofinsurance',
            'insurance_company_id',
            'quote_number',
            'policy_number',
            'issue_date',
            'start_date',
            'end_date',
          ],
        },
      ],
      view: {
        label: 'Policy Number',
        find: {
          basic: [],
          advanced: [],
        },
        columns: [
          {
            Header: 'ID',
            accessor: 'id',
          },
          {
            Header: 'Policy Number',
            accessor: 'policy_number',
          },
          {
            Header: 'Quote Number',
            accessor: 'quote_number',
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
        const {
          data,
          state,
          params: { id },
        } = options;
        if (state?.context === 'car_quote_snap') {
          return { ...data, car_quote_id: id };
        }
      },
    };
    return form;
  },
};
export default carQuotePolicy;
