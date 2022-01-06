const leadAttachment = {
  getForm() {
    const form = {
      db_table: 'car_quote_ftc_documents',
      title: 'Upload Documents',
      subtitle: 'document need for insurance purpose',
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
        write: ['advisor','oe', 'admin'],
        update: ['advisor','oe', 'admin'],
        delete: ['advisor','oe', 'admin'],
      },
      fields: {
        document: {
          type: 'dropdown',
          label: 'Choose Document',
          field: 'document',
          source: 'car_quote_documents',
          access: {
            read: [
              'advisor',
              'oe',
              'pa',
              'admin',
              'invoicing',
              'payment',
              'production_approval_manager',
            ],
            write: ['advisor', 'oe','admin'],
            update: ['advisor', 'oe', 'admin'],
          },
          rules: { required: true },
          defaultValue: '',
        },
        file_name: {
          type: 'file',
          label: 'Select Document',
          field: 'file_name',
          access: {
            read: [
              'advisor',
              'oe',
              'pa',
              'admin',
              'invoicing',
              'payment',
              'production_approval_manager',
            ],
            write: ['advisor','oe', 'admin'],
            update: ['advisor','oe', 'admin'],
          },
          defaultValue: '',
          rules: { required: true },
        },
      },
      sections: [
        {
          label: 'Upload Documents',
          fields: ['document', 'file_name'],
        },
      ],
      view: {
        label: 'Documents',
        find: {
          basic: [
            {
              type: 'dropdown',
              label: 'Select Document',
              field: 'document',
              source: 'car_quote_documents',
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
            Header: 'File Name',
            accessor: 'file_name',
          },
          {
            Header: 'Document Type',
            accessor: d => `${d?.document?.text}`,
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
          applyFilterAfterSearch(options) {
            const { params, filter } = options;
            return { ...filter, car_quote_id: params.id };
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
export default leadAttachment;
