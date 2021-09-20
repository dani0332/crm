import { getLocalStorage, setLocalStorage } from "../utils"


export function* afterSave(options) {

    const { body , initialForm } = options
    if(initialForm?.context === "car_quote_snap") {
        const obj = getLocalStorage("car_request_snap")
        obj.kyc_status_id = body.status
        setLocalStorage("car_request_snap", obj)
    }
};

export function* beforeSave(options) {


}

