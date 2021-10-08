

const ftcPayment = {
    getForm() {
        const form = {
            db_table: 'car_quote_payment',
            title: 'FTC Payment',
            subtitle: '',
            access: {
                read: ['pa', 'advisor' , 'admin', 'invoicing'],
                write: ['advisor' , 'admin'],
                update: ['advisor', 'admin'],
                delete: []
            },
            fields: {
                mode: {
                    type:'dropdown',
                    label:'Payment Mode',
                    source: ['CC', 'Non-CC'],
                    if: {
                        'CC': {
                            fields: [ 'method' ]
                        }
                    },
                    else: [ 'method' ],
                    field:'mode',
                    access: {
                         read: [ 'advisor', 'pa', 'admin', 'invoicing'],
                         write: [ 'advisor'],
                         update: ['advisor'],
                    },
                    rules: {required: true }
                },
                method: {
                    type:'dropdown',
                    label:'Payment Method',
                    source: ['Spotii', 'payments.insurancemarket.ae '],
                    field:'method',
                    offscreen: true,
                    access: {
                         read: [ 'advisor', 'pa', 'admin', 'invoicing'],
                         write: ['advisor' ],
                         update: [ 'advisor' ],
                    },
                    rules: { required: true }
                },
                comment: {
                    type:'textarea',
                    label:'Comment',
                    access: {
                         read: [ 'advisor', 'pa', 'admin', 'invoicing'],
                         write: ['advisor' ],
                         update: ['advisor'],
                    },
                    rules: {  }
                },
            },
            sections:[
                {
                    label: 'FTC Quote Status History',
                    fields: ['mode', 'method', 'comment']
                },
            ],
            view:{
                label: 'FTC Quote Status History',
                find:{
                    basic: [],
                    advanced: []
                },
                columns:[
                    {
                        Header: "ID",
                        accessor: "id"
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
                const { params , data } = options
                return  { ...data , car_quote_id : params.id}
            }
        }
        return form
    }
};
export default ftcPayment
