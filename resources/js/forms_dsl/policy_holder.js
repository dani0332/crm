const policyHolderDetail = {

    title: 'FTC Form',
    subtitle: '',
    fields: {
        first_name: {
            type:'text',
            label:'First Name',
            field: 'first_name',
            rules:{ required: true },
            defaultValue:''

        },
        last_name: {
            type:'text',
            label:'Last Name',
            field:'last_name',
            rules:{ required: true },
            defaultValue:''
        },
        email: {
            type:'text',
            label:'Email Address',
            field:'email',
            defaultValue:''
        },
        phone: {
            type:'text',
            label:'Phone Number',
            field:'phone',
            defaultValue:''
        },
        source: {
            type:'dropdown',
            label:'Nationality',
            field:'source',
            source: ['United Arab Emirates', 'Canada' , 'Turkey',  'China' ]
        },
        licence: {
            type: 'dropdown',
            label: 'UAE licence held for',
            field: 'licence',
            source: ['1 year', '2 year' , '3 year',  '4 year', '5 year' ]
        },
        date: {
            type: 'datePicker',
            label: 'Birth Date',
            field: 'date'
        }
    },
    sections:[
        {
            label: 'Policy Holder Detail',
            //fields: ['first_name']
             fields: ['first_name', 'last_name', 'email', 'phone', 'source', 'date', 'licence']
        }
    ]
};
export default policyHolderDetail
