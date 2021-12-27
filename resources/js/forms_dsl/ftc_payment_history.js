const ftcPaymentHistory = {
  getForm() {
    const form = {
      db_table: 'car_quote_payment_history',
      title: 'Payment History',
      subtitle: '',
      access: {
        read: [
          'pa',
          'advisor',
          'oe',
          'admin',
          'invoicing',
          'payment',
          'production_approval_manager',
        ],
        write: ['payment'],
        update: ['payment'],
        delete: [],
      },
      fields: {
        status: {
          type: 'dropdown',
          label: 'Status',
          source: ['Transaction Approved', 'Transaction Declined'],
          access: {
            read: [
              'advisor',
              'oe',
              'pa',
              'admin',
              'payment',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['payment'],
            update: ['payment'],
          },
          rules: { required: true },
        },
        notes: {
          type: 'textarea',
          label: 'Notes',
          access: {
            read: [
              'advisor',
              'oe',
              'pa',
              'admin',
              'payment',
              'invoicing',
              'production_approval_manager',
            ],
            write: ['payment'],
            update: ['payment'],
          },
          rules: { required: true },
        },
      },
      sections: [
        {
          label: 'FTC Payment Method',
          fields: ['status', 'notes'],
        },
      ],
      view: {
        label: 'Payment Method',
        find: {
          basic: [
            {
              type: 'dropdown',
              label: 'Status',
              field: 'status',
              source: ['Transaction Approved', 'Transaction Declined'],
            },
          ],
          advanced: [],
        },
        columns: [
          {
            Header: 'ID',
            accessor: 'id',
          },
          {
            Header: 'Status',
            accessor: 'status',
          },
          {
            Header: 'Approval Code',
            accessor: 'notes',
          },
        ],
        events: {
          onClick(options) {
            const { row, dispatch, history } = options;
            window.open(
              '/transapp/showtransaction?approval_code=' + row.notes,
              '_blank',
            );
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
export default ftcPaymentHistory;
