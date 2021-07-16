
import * as yup from 'yup';

const leadAttachment = {
    title: 'Document Attachment',
    subtitle: 'document need for insurance purpose',
    fields: {
        source: {
            type:'dropdown',
            label:'Choose Document',
            field:'source',
            source: ["Driving Licence", "National ID", "Passport ID"],
            defaultValue:''
        },
        document: {
            type:'file',
            label:'Upload Document',
            field:'document',
            defaultValue:''
        },
    },
    sections:[
        {
            label: 'Document Attachment',
            fields: ['source', 'document']
        },
    ]
};

export default leadAttachment
