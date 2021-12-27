let vehicleSubform = {
  getForm() {
    const yuu = {
      db_table: 'car_quote_request',
      title: 'Vehicle Detail Subform',
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
        write: ['advisor', 'oe', 'admin'],
        update: ['advisor', 'oe', 'admin'],
        delete: ['advisor', 'oe', 'admin'],
      },
      subtitle: '',
      fields: {
        engine_capacity: {
          type: 'text',
          label: 'Engine Capacity',
          rules: { required: true },
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
            write: ['oe','advisor', 'admin'],
            update: ['oe','advisor', 'admin'],
          },
        },
        cylinder: {
          type: 'text',
          label: 'Cylinders',
          rules: { required: true },
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
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
          },
        },
        chassis_number: {
          type: 'text',
          label: 'Chassis Number',
          rules: { required: true },
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
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
          },
        },
        engine_number: {
          type: 'text',
          label: 'Engine Number',
          rules: { required: true },
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
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
            access: {
              read: ['advisor', 'oe', 'pa', 'admin'],
              write: ['advisor', 'oe', 'admin'],
              update: ['advisor', 'oe', 'admin'],
            },
          },
        },
        date_first_registration: {
          type: 'datePicker',
          label: 'Date of first registration',
          field: 'date_first_registration',
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
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
          },
        },
        currently_insured_with:{
          type: 'text',
          label: 'Cyurrently With (Insurer Name)',
          rules: { required: true },
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
            write: [ 'oe','advisor'],
            update: [ 'oe','advisor'],
          },
        },
        vehicle_color: {
          type: 'text',
          label: 'Color of the vehicle',
          rules: { required: true },
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
            write: [ 'oe','advisor', 'admin'],
            update: [ 'oe','advisor', 'admin'],
          },
        },
        seating_capacity: {
          type: 'text',
          label: 'Seating Capacity',
          rules: { required: true },
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
            write: [ 'oe','advisor', 'admin'],
            update: [ 'oe','advisor', 'admin'],
          },
        },
        specs: {
          type: 'dropdown',
          source: ['GCC', 'NON GCC', 'IMPORTED GCC'],
          label: 'Spec',
          rules: { required: true },
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
            write: [ 'oe','advisor', 'admin'],
            update: [ 'oe','advisor', 'admin'],
          },
        },
        current_cover: {
          type: 'dropdown',
          source: [
            'Comprehensive',
            'TPL',
            'Lapse in Insurance'
          ],
          label: 'Current Cover',
          rules: { required: true },
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
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor', 'oe', 'admin'],
          },
        },
        vehicle_modified: {
          type: 'dropdown',
          source: ['Yes', 'No'],
          label: 'Vehicle Modified',
          rules: { required: true },
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
            write: ['advisor', 'oe', 'admin'],
            update: ['advisor',  'oe','admin'],
          },
        },
      },
      sections: [
        {
          label: '',
          fields: [
            'engine_capacity',
            'cylinder',
            'chassis_number',
            'engine_number',
            'date_first_registration',
            'currently_insured_with',
            'vehicle_color',
            'seating_capacity',
            'specs',
            'current_cover',
            'vehicle_modified',
          ],
        },
      ],
      view: {
        find: {
          basic: [],
          advanced: [],
        },
        columns: [
          {
            Header: 'Manufacture Year',
            accessor: 'year_of_manufacture',
          },
        ],
      },
    };
    return yuu;
  },
};

export default { ...vehicleSubform };
