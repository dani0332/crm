

let vehicleSubform = {

    getForm() {
        const yuu = {
            db_table: 'car_quote_request',
            title: 'Vehicle Detail Subform',
            access: {
                read: ['advisor', 'pa', 'admin', 'invoicing'],
                write: [ 'advisor','admin'],
                update: [ 'advisor','admin'],
                delete: ['advisor','admin' ]
           },
            subtitle: '',
            fields: {
                engine_capacity: {
                    type : 'text',
                    label: 'Engine Capacity',
                    rules: { required: true },
                    access: {
                        read: ['advisor', 'pa', 'admin', 'invoicing'],
                        write: [ 'advisor','admin'],
                        update: [ 'advisor','admin'],
                   },
                },
                cylinder: {
                    type: 'text',
                    label: 'Cylinders',
                    rules: { required: true },
                    access: {
                        read: ['advisor', 'pa', 'admin', 'invoicing'],
                        write: [ 'advisor','admin'],
                        update: [ 'advisor','admin'],
                   },
                },
                chassis_number:{
                    type: 'text',
                    label: 'Chassis Number',
                    rules: { required: true },
                    access: {
                        read: ['advisor', 'pa', 'admin', 'invoicing'],
                        write: [ 'advisor','admin'],
                        update: [ 'advisor','admin'],
                   }
                },
                engine_number: {
                    type: 'text',
                    label: 'Engine Number',
                    rules: { required: true },
                    access: {
                        read: ['advisor', 'pa', 'admin', 'invoicing'],
                        write: [ 'advisor','admin'],
                        update: [ 'advisor','admin'],
                        access: {
                            read: ['advisor', 'pa', 'admin'],
                            write: [ 'advisor','admin'],
                            update: [ 'advisor','admin'],
                       },
                   }
                },
                date_first_registration: {
                    type: 'datePicker',
                    label: 'Date of first registration',
                    field: 'date_first_registration',
                    access: {
                        read: ['advisor', 'pa', 'admin', 'invoicing'],
                        write: [ 'advisor','admin'],
                        update: ['advisor', 'admin'],
                   },
                },
                vehicle_color:{
                    type: 'text',
                    label: 'Color of the vehicle',
                    rules: { required: true },
                    access: {
                        read: ['advisor', 'pa', 'admin', 'invoicing'],
                        write: [ 'advisor','admin'],
                        update: [ 'advisor','admin'],
                   }
                },
                seating_capacity:{
                    type: 'text',
                    label: 'Seating Capacity',
                    rules: { required: true },
                    access: {
                        read: ['advisor', 'pa', 'admin', 'invoicing'],
                        write: [ 'advisor','admin'],
                        update: [ 'advisor','admin'],
                   },
                },
                specs:{
                    type: 'dropdown',
                    source: ['GCC', 'NON GCC', 'IMPORTED GCC'],
                    label: 'Spec',
                    rules: { required: true },
                    access: {
                        read: ['advisor', 'pa', 'admin', 'invoicing'],
                        write: [ 'advisor','admin'],
                        update: [ 'advisor','admin'],
                   },
                },
                current_cover:{
                    type: 'dropdown',
                    source: ['Comprehensive', 'TPL', 'Lapse in Insurance'],
                    label: 'Current Cover',
                    rules: { required: true },
                    access: {
                        read: ['advisor', 'pa', 'admin', 'invoicing'],
                        write: [ 'advisor','admin'],
                        update: [ 'advisor','admin'],
                   },
                },
                vehicle_modified:{
                    type: 'dropdown',
                    source: ['Yes', 'No'],
                    label: 'Vehicle Modified',
                    rules: { required: true },
                    access: {
                        read: ['advisor', 'pa', 'admin', 'invoicing'],
                        write: [ 'advisor','admin'],
                        update: [ 'advisor','admin'],
                   },
                }
            },
            sections:[
                {
                    label: 'Add Vehicle Detail',
                    fields: ['engine_capacity', 'cylinder', 'chassis_number',
                    'engine_number','date_first_registration', 'vehicle_color', 'seating_capacity', 'specs', 'current_cover', 'vehicle_modified']
                }
            ],
            view:{
                find:{
                    basic: [],
                    advanced: []
                },
                columns:[
                    {
                        Header: "Manufacture Year",
                        accessor: "year_of_manufacture"
                    }
                ]
            },
        }
        return yuu
    },
};

export default { ...vehicleSubform }
