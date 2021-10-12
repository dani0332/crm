

export function* fillConditionalFields(obj) {

    const { fields, selectedRecord } = obj
    Object.entries(fields).forEach(entry => {
        const [key, value] = entry;
        const getField = value
        const recVal = selectedRecord?.[key]

        if(recVal && getField?.if) {
            const val = getField.if[recVal]
            if(val?.fields){
                const getCondFields = getField.if[recVal].fields
                if(getCondFields.length > 0) {
                    getCondFields.forEach(element => {
                        obj.fields[element].offscreen = false
                    });
                }
                console.log(getCondFields)
            } else {
                const getCondFields = getField.else
                getCondFields.forEach(element => {
                    obj.fields[element].offscreen = true
                    obj.fields[element].value = null
                });
            }
        }
    });
}
