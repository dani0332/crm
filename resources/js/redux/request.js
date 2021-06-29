
export function* sendRequest(obj) {
    const { post } = obj
    const { method, form, params, action, id } = post
    let url = {}
    let request = {}
    try {
        const response = yield fetch(url, request)
        if (!response.ok) {
            obj.reject('Something went wrong. !response.ok')
            return
        }
        const data = yield response.json()
        if (obj?.resolve) {
            obj.resolve(data)
        }
    } catch (error) {
        if (obj?.reject) {
            obj.reject()
        }
    }
}

