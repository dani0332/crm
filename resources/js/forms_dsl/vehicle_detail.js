const vehicleDetail = {

    readOnly:true,
    db_table: 'car_quote_request',
    mode: 'vehicle_detail',
    title: 'Vehicle Detail',
    subtitle: '',
    fields: {
        year_of_manufacture: {
            type: 'text',
            label: 'Year of Manufacture',
            field: 'year_of_manufacture'
        },
        car_model_id: {
            type: 'dropdown',
            label: 'Car Model',
            field: 'car_model_id',
            transform(data) {
                const values = data.map(function (item) {
                    return  { value: item.id, label: item.text };
                });
                return values
            },
        },
        car_make_id: {
            type: 'dropdown',
            label: 'Car Make',
            field: 'car_make_id',
            transform(data) {
                const values = data.map(function (item) {
                    return  { value: item.id, label: item.text };
                });
                return values
            },
        }
    },
    sections:[
        {
            label: 'Vehicle Detail',
            fields: ['year_of_manufacture', 'car_model_id', 'car_make_id']
        }
    ]
};

export default vehicleDetail
