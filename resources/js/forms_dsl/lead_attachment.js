
import * as yup from 'yup';

const leadAttachment = {
    title: 'Document Attachment',
    subtitle: 'document need for insurance purpose',
    fields: [
        {
            type:'dropdown',
            label:'Choose Document',
            field:'source',
            source: ["Driving Licence", "National ID", "Passport ID"]
        },
        {
            type:'file',
            label:'Upload Document',
            field:'document'
        },
    ]
};

export default leadAttachment
