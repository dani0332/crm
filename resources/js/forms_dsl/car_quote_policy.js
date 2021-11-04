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
          'admin',
          'invoicing',
          'production_approval_manager',
        ],
        write: ['advisor', 'admin'],
        update: ['advisor', 'admin'],
        delete: [],
      },
      fields: {
        quote_number: {
          type: 'text',
          label: 'Quote Number',
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
        policy_number: {
          type: 'text',
          label: 'Policy Number',
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
        issue_date: {
          type: 'datePicker',
          label: 'Policy Issue Date',
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
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['advisor', 'admin'],
            update: ['advisor', 'admin'],
          },
        },
        end_date: {
          type: 'datePicker',
          label: 'Policy End Date',
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
          },
        },
        approval_code: {
          type: 'text',
          label: 'Approval Code',
          access: {
            read: [
              'pa',
              'advisor',
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
          },
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
              'admin',
              'invoicing',
              'production_approval_manager',
            ],
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
      },
      sections: [
        {
          label: 'Transcation',
          fields: ['approval_code', 'insurance_company_id'],
        },
        {
          label: 'Car Quote Policy Record',
          fields: [
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
