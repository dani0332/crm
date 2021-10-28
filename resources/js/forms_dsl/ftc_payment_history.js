const ftcPaymentHistory = {
    getForm() {
        const form = {
            db_table: 'car_quote_payment_history',
            title: 'FTC Payment History',
            subtitle: '',
            access: {
                read: ['pa', 'advisor' , 'admin', 'invoicing','production_approval_manager'],
                write: ['invoicing' ],
                update: ['invoicing'],
                delete: []
            },
            fields: {
                status: {
                    type:'dropdown',
                    label:'Status',
                    source: ['Transaction Approved','Transaction Declined'],
                    access: {
                         read: [ 'advisor', 'pa', 'admin', 'invoicing','production_approval_manager'],
                         write: [ 'invoicing'],
                         update: ['invoicing' ],
                    },
                    rules: {required: true }
                },
                notes: {
                    type:'textarea',
                    label:'Notes',
                    access: {
                         read: [ 'advisor', 'pa', 'admin', 'invoicing','production_approval_manager'],
                         write: [ 'invoicing' ],
                         update: [ 'invoicing' ],
                    },
                    rules: { required: true }
                }
            },
            sections:[
                {
                    label: 'FTC Payment Method',
                    fields: ['status', 'notes']
                },
            ],
            view:{
                label: 'FTC Payment Method',
                find:{
                    basic: [
                        {
                        type:'dropdown',
                        label:'Status',
                        field: 'status',
                        source: ['Transaction Approved','Transaction Declined']
                    },
                ],
                    advanced: []
                },
                columns:[
                    {
                        Header: "ID",
                        accessor: "id"
                    },
                    {
                        Header: "Status",
                        accessor: "status"
                    },
                    {
                        Header: "Notes",
                        accessor: "notes"
                    }
                ],
                events: {
                    applyFilter(options){
                        const { mode, url , params: { id } } = options
                        const generateUrl = `/?filter={"car_quote_id":"${id}"}`
                        return generateUrl
                    }
                }
            },
            postTransform(options){
                const { data, state , params : { id }} = options
                if(state?.context === 'car_quote_snap'){
                    return { ...data, car_quote_id: id}
                }
            }
        }
      },
    };
    return form;
  },
};
export default ftcPaymentHistory;
