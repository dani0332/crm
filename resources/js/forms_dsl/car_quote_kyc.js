const carQuoteKycStatus = {
  getForm() {
    const form = {
      db_table: 'car_quote_kyc',
      title: 'KYC',
      subtitle: '',
      access: {
        read: ['advisor', 'pa', 'admin', 'invoicing'],
        write: ['advisor', 'admin'],
        update: ['advisor', 'admin'],
        delete: ['admin'],
      },
      fields: {
        designation: {
          type: 'text',
          label: 'Designation',
          source: 'kyc_statuses',
          rules: { required: true },
          access: {
            read: ['advisor', 'pa', 'advisor', 'admin', 'invoicing'],
            write: ['advisor', 'admin'],
            update: ['advisor', 'admin'],
          },
        },
        organization: {
          type: 'text',
          label: 'Organization',
          access: {
            read: ['advisor', 'pa', 'admin', 'invoicing'],
            write: ['advisor', 'admin'],
            update: ['advisor', 'admin'],
          },
          rules: { required: true },
        },
        profession: {
          type: 'dropdown',
          label: 'Profession',
          source: [
            'Business Owner',
            'CEO/Managing Director/Managing Partner',
            'Human Resources (HR)',
            'Administration/Operations/Secretarial/Assistant/Customer Service Executive',
            'Sales/Business Development',
            'Finance or Accounting',
            'Consultant/Self Employed',
            'Healthcare Professional: Doctor, Nurse, Pharmacist, Diagnostician etc',
            'Teacher/Instructor/Coach',
            'Real Estate Agent',
            'Engineer/Architect/Contractor',
            'Pilot',
            'Chef',
            'Technician/Technical Manager/Quality Controller/IT/Software Developer/Analyst',
            'Lawyer',
            'Government Official: Police, Municipality, Court etc',
            'Driver',
            'Marketing/Media/Advertising',
            'Artist/Actor/Writer/Sportsperson',
            'House Wife',
            'Unemployed',
          ],
          access: {
            read: ['advisor', 'pa', 'admin', 'invoicing'],
            write: ['advisor', 'admin'],
            update: ['advisor', 'admin'],
          },
          rules: { required: true },
        },
      },
      sections: [
        {
          label: 'KYC Profession/Organization',
          fields: ['profession', 'organization', 'designation'],
        },
      ],
      view: {
        label: 'KYC ',
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
            Header: 'Profession',
            accessor: `profession`,
          },
          {
            Header: 'Organization',
            accessor: `organization`,
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
export default carQuoteKycStatus;
