const carQuoteKycStatus = {
  getForm() {
    const form = {
      db_table: 'car_quote_kyc_status',
      title: 'KYC Status',
      subtitle: '',
      access: {
        read: ['advisor', 'pa', 'admin', 'invoicing'],
        write: ['pa', 'admin'],
        update: [],
        delete: ['admin'],
      },
      fields: {
        status: {
          type: 'dropdown',
          label: 'Choose Status',
          field: 'status',
          source: 'kyc_statuses',
          rules: { required: true },
          access: {
            read: ['advisor', 'pa', 'advisor', 'admin', 'invoicing'],
            write: ['pa', 'admin'],
            update: ['pa', 'admin'],
          },
        },
        notes: {
          type: 'textarea',
          label: 'Notes',
          access: {
            read: ['advisor', 'pa', 'admin', 'invoicing'],
            write: ['pa', 'admin', 'invoicing'],
            update: ['pa', 'admin', 'invoicing'],
          },
          rules: { required: true },
        },
      },
      sections: [
        {
          label: 'KYC Status',
          fields: ['status', 'notes'],
        },
      ],
      view: {
        label: 'KYC Status',
        find: {
          basic: [
            {
              type: 'dropdown',
              label: 'Choose Status',
              field: 'status',
              source: 'kyc_statuses',
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
            Header: 'status',
            accessor: d => `${d?.status?.text}`,
          },
          {
            Header: 'Notes',
            accessor: d => `${d?.notes?.substring(0, 80)}...`,
          },
        ],
        events: {
          applyFilterAfterSearch(options) {
            const { params, filter } = options;
            return { ...filter, car_quote_id: params.id };
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
export default carQuoteKycStatus;
