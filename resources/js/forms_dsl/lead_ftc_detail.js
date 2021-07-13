
import * as yup from 'yup';

const ftcDetail = {

    title: 'FTC Form',
    subtitle: 'vehicle & policy information',
    fields: [
        {
            type:'text',
            label:'CDB',
            field:'code',
            defaultValue:''

        },
        {
            type:'text',
            label:'Email',
            field:'email',
            defaultValue:''
        },
        {
            type:'dropdown',
            label:'Nationality',
            field:'source',
            // sourcefilter:
			// 	role:
			// 		'static': 'pharm'
			// 	group_role:
			// 		'static': '!tech'
           // source: 'leads',
            //template: ` `
            source: [{ id: 1, title: 'Pakistani' }, { id: 2, title: 'India' }],
            defaultValue: 1
            //source: ["One", "Two", "Three"]
        }

    ],
    schema: yup.object().shape({
        code: yup.string().required().min(4),
        email: yup.string().email()
      })
};

export default ftcDetail
