import FTCDetail from "../components/input-fields/ftc-detail";
const ftcHistory = {
    getForm() {
        const form = {
            db_table: 'ftc_history',
            title: 'FTC History',
            subtitle: '',
            access: {
                read: ['pa', 'advisor' , 'admin', 'invoicing','production_approval_manager'],
                write: ['advisor' , 'admin'],
                update: ['advisor', 'admin'],
                delete: [ 'advisor' , 'admin', 'admin' ]
            },
            fields: {
                status: {
                    type:'dropdown',
                    label:'Status',
                    source: ['Resubmit for Approval'],
                    access: {
                         read: [ 'advisor', 'pa', 'admin', 'invoicing','production_approval_manager'],
                         write: [ 'advisor'],
                         update: [ ],
                    },
                    rules: {required: true }
                },
                data: {
                    type:'textarea',
                    label:'Notes',
                    access: {
                        read: [ 'advisor', 'pa' ,'production_approval_manager'],
                        write: [ 'advisor'],
                        update: [ 'advisor' ],
                   },
                   rules: {required: true },
                   shouldRenderForRead(field){

                        console.log('------ShouldRender----')
                        console.log(field?.selectedRecord)
                        console.log('------ShouldRender----')
                        if(field?.selectedRecord?.status !== "FTC Sent")
                            return false
                        return true
                   },
                   renderForRead(field){
                        return <FTCDetail field={field}/>
                   }
                },
            },
            sections:[
                {
                    label: 'FTC History',
                    fields: ['status', 'data']
                },
            ],
            view:{
                label: 'FTC History',
                find:{
                    basic: [
                    {
                        type:'dropdown',
                        label:'Status',
                        field:'status',
                        source: [ 'FTC Sent', 'Resubmit for Approval'],
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
                        Header: "status",
                        accessor: "status"
                    }
                ],
                events: {
                    applyFilter(options){
                        const { mode, url , params: { id } } = options
                        const queryMode = { mode }
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
export default ftcHistory
