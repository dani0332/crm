
import * as carRequestKycStatus from "./car_quote_kyc_status";
import * as carQuoteRequest from "./car_quote_request";

export function* getFormHook(options)  {

    const { form } = options
    switch(form) {
        case 'car_quote_kyc_status':{
            return carRequestKycStatus
        }
        case 'car_quote_request':
        case 'leadRequest':
        {
            return carQuoteRequest
        }
        default:
            return null
    }
}

