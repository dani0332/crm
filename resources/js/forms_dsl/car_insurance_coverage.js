let insuranceDetail = {
    getForm() {

        const yuu = {
            db_table: 'car_quote_insurance_coverage',
            mode: 'insurance_coverage_detail',
            title: 'Insurance Coverage Information',
            subtitle: '',
            access: {
                read: [ 'pa','advisor' , 'admin', 'invoicing'],
                write: [ 'advisor' ,'admin'],
                update: [ 'advisor' ,'admin'],
                delete: []
            },
            fields: {
                start_date: {
                    type: 'datePicker',
                    label: 'Policy start date',
                    access: {
                        read: [ 'pa','advisor','admin', 'invoicing'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    // rules: { required: true }
                },
                insurance_company_id: {
                    type: 'dropdown',
                    label: 'Insurance Company',
                    source: 'insurance_companies',
                    rules: { required: true },
                    access: {
                        read: [ 'pa','advisor','admin', 'invoicing'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    transform(item) {

                        if( Array.isArray(item) ){
                            const items = item.map((u,i) => {
                                return { value: u?.id , label:u?.name}
                            })
                            return items
                        }
                        else
                            return  { value: item?.id, label: item?.name };
                   },
                },
                insurance_plan_id: {
                    type: 'dropdown',
                    label: 'Insurance Plan',
                    source: 'car_quote_insurance_plan',
                    access: {
                        read: [ 'pa','advisor','admin', 'invoicing'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    rules: { required: true }
                },
                vehicle_type_id: {
                    type: 'dropdown',
                    label: 'Vehicle Type',
                    source: 'vehicle_type',
                    access: {
                        read: [ 'pa','advisor','admin', 'invoicing'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    rules: { required: true }
                },
                excess: {
                    type: 'text',
                    label: 'Excess',
                    access: {
                        read: [ 'advisor','admin', 'invoicing' , 'pa'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    rules: { required: true }
                },
                premium_price:{
                    type: 'text',
                    label: 'Premium/Price',
                    access: {
                        read: [ 'pa','advisor','admin', 'invoicing'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    rules: { required: true }
                },
                ancillary_excess: {
                    type: 'text',
                    label: 'Ancillary Excess',
                    access: {
                        read: [ 'pa','advisor','admin', 'invoicing'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    rules: { required: true }
                },
                personal_accident_benefit:{
                    type: 'dropdown',
                    label: 'Personal Accident Benefit',
                    access: {
                        read: [ 'pa','advisor','admin', 'invoicing'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    source: ['INCLUDED',  'NOT INCLUDED']
                },
                breakdown_recovery: {
                    type: 'dropdown',
                    label: 'Breakdown recovery',
                    source: ['INCLUDED',  'NOT INCLUDED'],
                    access: {
                        read: [ 'pa','advisor','admin', 'invoicing'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    rules: { required: true }
                },
                off_road_cover: {
                    type: 'dropdown',
                    label: 'Off-road cover (for 4X4 only)',
                    source: ['INCLUDED',  'NOT INCLUDED'],
                    access: {
                        read: [ 'pa','advisor','admin', 'invoicing'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    rules: { required: true }
                },
                rend_a_car: {
                    type: 'dropdown',
                    label: 'Rent a Car',
                    source: ['INCLUDED',  'NOT INCLUDED'],
                    access: {
                        read: [ 'pa','advisor','admin', 'invoicing'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    rules: { required: true }
                },
                repair_type: {
                    type: 'dropdown',
                    label: 'Repair Type',
                    source: ['Agency',  'NON Agency'],
                    access: {
                        read: [ 'pa','advisor','admin', 'invoicing'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    rules: { required: true }
                },
                financed_by: {
                    type: 'text',
                    label: 'Financed By',
                    access: {
                        read: [ 'pa','advisor','admin', 'invoicing'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    rules: { required: true }
                },
                geographical_area: {
                    type: 'text',
                    label: 'Geographical Area',
                    access: {
                        read: [ 'pa','advisor','admin', 'invoicing'],
                        write: [ 'advisor' ,'admin'],
                        update: [ 'advisor' ,'admin'],
                    },
                    rules: { required: true }
                },
            },
            sections:[
                {
                    label: 'Insurance Coverage Information',
                    //fields: ['start_date', 'insurance_company_id']
                    fields: ['start_date', 'insurance_company_id','insurance_plan_id',
                    'vehicle_type_id','excess','premium_price','ancillary_excess','personal_accident_benefit','breakdown_recovery','off_road_cover','rend_a_car','repair_type','financed_by','geographical_area']

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
                ],
                events: {
                    applyFilter(options){
                        const { mode, url , params: { id } } = options
                        const queryMode = { mode }
                        const queryParams = new URLSearchParams(queryMode);
                        const generateUrl = `/?filter={"car_quote_id":"${id}"}`
                        return generateUrl
                    },
                    afterFetchData(options){
                        const { resp , dispatch , history } = options
                        dispatch({ type: 'VISIBLE_FORM', object: resp.data[0], formState: 'read' })
                        return resp.data
                    }
                }
            },
            postTransform(options){
                const { params , data } = options
                return  { ...data , car_quote_id : params.id}
            }
        }
        return yuu
    },
};

export default { ...insuranceDetail }
