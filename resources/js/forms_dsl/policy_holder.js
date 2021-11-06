const policyHolderDetail = {
  getForm() {
    const dslForm = {
      db_table: 'car_quote_request',
      title: 'FTC Form',
      multi: false,
      subtitle: '',
      access: {
        read: [
          'advisor',
          'pa',
          'admin',
          'invoicing',
          'production_approval_manager',
        ],
        write: ['admin'],
        update: ['admin'],
        delete: ['admin'],
      },
      fields: {
        first_name: {
          type: 'text',
          label: 'First Name',
          field: 'first_name',
          rules: { required: true },
          defaultValue: '',
        },
        last_name: {
          type: 'text',
          label: 'Last Name',
          field: 'last_name',
          rules: { required: true },
          defaultValue: '',
        },
        email: {
          type: 'text',
          label: 'Email Address',
          field: 'email',
          rules: { required: true },
          defaultValue: '',
        },
        mobile_no: {
          type: 'text',
          label: 'Phone Number',
          field: 'mobile_no',
          defaultValue: '',
        },
        nationality_id: {
          type: 'dropdown',
          label: 'Nationality',
          field: 'nationality_id',
          form: 'nationality',
          transform(item) {
            // const values = data.map(function (item) {
            //     return  { value: item.id, label: item.code };
            // });
            return { value: item?.id, label: item?.code };
            // return values
          },
        },
        dob: {
          type: 'datePicker',
          label: 'Birth Date',
          field: 'dob',
          value: '12/12/2009',
        },
      },
      sections: [
        {
          label: 'Policy Holder Detail',
          fields: [
            'first_name',
            'last_name',
            'email',
            'mobile_no',
            'dob',
            'nationality_id',
          ],
        },
      ],
      view: {
        find: {
          basic: [
            {
              type: 'text',
              label: 'First Name',
              field: 'first_name',
              validation: {},
            },
            {
              type: 'text',
              label: 'Last Name',
              field: 'last_name',
              validation: {},
            },
            {
              type: 'text',
              label: 'Email',
              field: 'email',
              validation: {},
            },
            {
              type: 'text',
              label: 'Mobile No',
              field: 'mobile_no',
              validation: {},
            },
          ],
          advanced: [],
        },
        columns: [
          {
            Header: 'First Name',
            accessor: 'first_name',
          },
          {
            Header: 'Last Name',
            accessor: 'last_name',
          },
          {
            Header: 'Email',
            accessor: 'email',
          },
          {
            Header: 'Contact Number',
            accessor: 'mobile_no',
          },
        ],
        events: {
          applyFilter(options) {
            const {
              mode,
              params: { id },
            } = options;
            const queryMode = { mode };
            const queryParams = new URLSearchParams(queryMode);
            const generateUrl = `/${id}/?${queryParams.toString()}`;
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
    };
    return dslForm;
  },
};
export default policyHolderDetail;
