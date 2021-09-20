
import * as carRequestKycStatus from "./car_quote_kyc_status";

export function* getFormHook(options)  {

    const { form } = options
    switch(form) {
        case 'car_quote_kyc_status':{
            return carRequestKycStatus
        }
        default:
            return null
    }
}

