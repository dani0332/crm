
const leadAttachment = {
    getForm() {
        const form = {
            db_table: 'car_quote_ftc_documents',
            title: 'Document Attachment',
            subtitle: 'document need for insurance purpose',
            access: {
                read: ['pa', 'advisor' , 'admin', 'invoicing'],
                write: [ 'advisor' ,'admin'],
                update: ['advisor' , 'admin'],
                delete: ['advisor','admin']
            },
            fields: {
                document: {
                    type:'dropdown',
                    label:'Choose Document',
                    field:'document',
                    source: "car_quote_documents",
                    access: {
                         read: [ 'advisor', 'pa', 'admin', 'invoicing'],
                         write: ['advisor',  'admin'],
                         update: [ 'advisor','admin'],
                    },
                    rules: { required: true },
                    defaultValue:''
                },
                file_name: {
                    type:'file',
                    label:'Upload Document',
                    field:'file_name',
                    access: {
                        read: ['advisor', 'pa', 'admin', 'invoicing'],
                        write: [ 'advisor', 'admin'],
                        update: [ 'advisor','admin'],
                   },
                    defaultValue:'',
                    rules: { required: true },
                },
            },
            sections:[
                {
                    label: 'Document Attachment',
                    fields: ['document', 'file_name']
                },
            ],
            view:{
                label: 'Documents',
                find:{
                    basic: [
                    {
                        type:'dropdown',
                        label:'Choose Document',
                        field:'document',
                        source: "car_quote_documents"
                    }
                ],
                    advanced: []
                },
                columns:[
                    {
                        Header: "ID",
                        accessor: "id"
                    },
                    {
                        Header: "File Name",
                        accessor: "file_name"
                    },
                    {
                        Header: "Document",
                        accessor: d => `${d?.document?.text}`
                    }
                ],
                events: {
                    applyFilter(options){
                        const { params: { id } } = options
                        const generateUrl = `/?filter={"car_quote_id":"${id}"}`
                        return generateUrl
                    },
                    applyFilterAfterSearch(options){
                        const { params, filter } = options
                        return { ...filter, car_quote_id: params.id }
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
        return form
    }
};
export default leadAttachment
