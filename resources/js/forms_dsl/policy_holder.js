const policyHolderDetail = {

    readOnly:true,
    db_table: 'car_quote_request',
    mode: 'policy_holder_detail',
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
        mobile_no: {
            type:'text',
            label:'Phone Number',
            field:'mobile_no',
            defaultValue:''
        },
        emirate_of_registration_id: {
            type: 'dropdown',
            label: 'UAE licence held for',
            field: 'emirate_of_registration_id',
            form: 'emirates',
            transform(data) {
                const values = data.map(function (item) {
                    return  { value: item.id, label: item.code };
                });
                return values
            },
        },
        dob: {
            type: 'datePicker',
            label: 'Birth Date',
            field: 'dob',
            readOnly:true,
            value: '12/12/2009',
        }
    },
    sections:[
        {
            label: 'Policy Holder Detail',
             fields: ['first_name', 'last_name', 'email', 'mobile_no', 'emirate_of_registration_id', 'dob']
        }
    ]
};
export default policyHolderDetail
