export function* sendRequest(url,request, options) {

    try {
        const response = yield fetch(url, request)
        if (!response.ok) {
            options?.reject()
            throw "Invalid request"
        }
        const data = yield response.json()
        console.log(`**************${url}->data ******************`)
        console.log(data)
        console.log(`**************${url}->data ******************`)
        if(options?.resolve  && typeof options.resolve=== 'function')
            options.resolve(data)
        else
            return data

    } catch (error) {
        if(options?.reject  && typeof options.reject=== 'function')
            options.reject(data)
        console.log(`**************${url}->Error ******************`)
        console.log(error)
        console.log(`**************${url}->Error ******************`)

    }
}

