import moment from 'moment';

const ftcQuoteStatusHistory = {
  getForm() {
    const form = {
      db_table: 'ftc_quote_status_history',
      title: 'FTC Quote Status History',
      subtitle: '',
      access: {
        read: ['pa', 'advisor', 'admin', 'invoicing'],
        write: [],
        update: [],
        delete: [],
      },
      fields: {
        quote_status_id: {
          type: 'dropdown',
          label: 'Status',
          source: 'quote_status',
          field: 'quote_status_id',
          access: {
            read: ['advisor', 'pa', 'admin', 'invoicing'],
            write: [],
            update: [],
          },
          rules: { required: true },
        },
        notes: {
          type: 'textarea',
          label: 'Notes',
          access: {
            read: ['advisor', 'pa', 'admin', 'invoicing'],
            write: [],
            update: [],
          },
          rules: { required: true },
        },
        created_by: {
          type: 'text',
          label: 'Created By',
        },
        created_at: {
          type: 'text',
          label: 'Created By',
        },
      },
      sections: [
        {
          label: 'FTC Quote Status History',
          fields: ['quote_status_id', 'notes', 'created_by', 'created_at'],
        },
      ],
      view: {
        label: 'FTC Quote Status History',
        find: {
          basic: [
            {
              type: 'dropdown',
              label: 'Status',
              field: 'quote_status_id',
              source: 'quote_status',
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
            accessor: d => `${d?.quote_status_id?.text}`,
          },
          {
            Header: 'Created By',
            accessor: 'created_by',
          },
          {
            Header: 'Created At',
            accessor: d =>
              `${moment(d?.created_at).format('MMMM Do YYYY, h:mm:ss a')}`,
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
    };
    return form;
  },
};
export default ftcQuoteStatusHistory;
