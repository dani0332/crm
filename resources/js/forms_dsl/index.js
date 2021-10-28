import leadAttachment from "./lead_attachment";
import ftcDetail from "./lead_ftc_detail";
import policyHolderDetail from "./policy_holder";
import vehicleDetail from "./vehicle_detail"
import leadRequest from "./car_requests";
import insuranceDetail from "./car_insurance_coverage"
import vehicleSubform from "./vehicle-subform";
import ftcHistory from "./ftc_history";
import carQuoteKycStatus from "./car_quote_kyc_status";
import carQuoteKyc from "./car_quote_kyc";
import ftcQuoteStatusHistory from "./ftc_quote_status_history";
import ftcPayment from "./ftc_payment";
import ftcPaymentHistory from "./ftc_payment_history";
import teams from "./teams";

const getDSLForm = (options) => {
    const { form } = options
    switch(form){
        case 'vehicleDetail':
            return vehicleDetail.getForm()
        case 'vehicleSubform':
            return vehicleSubform.getForm()
        case 'policyHolderDetail':
            return policyHolderDetail.getForm()
        case 'leadAttachment' :
            return leadAttachment.getForm()
        case 'leadRequest' :
            return leadRequest.getForm()
        case 'carInsuranceDetail':
            return insuranceDetail.getForm()
        case 'ftcHistory':
            return ftcHistory.getForm()
        case 'ftcPayment':
            return ftcPayment.getForm()
        case 'ftcPaymentHistory':
            return ftcPaymentHistory.getForm()
        case 'ftcQuoteStatusHistory' :
            return ftcQuoteStatusHistory.getForm()
        case 'carQuoteKycStatus':
            return carQuoteKycStatus.getForm()
        case 'carQuoteKyc':
            return carQuoteKyc.getForm()
        case 'teams':
            return teams.getForm()
    }
}

const getFormObjForDraw = (obj) => {
    const { form  } = obj
    let formObj = getDSLForm( { form })
    return formObj
}

export  { getFormObjForDraw, getDSLForm, leadAttachment, ftcDetail, policyHolderDetail, vehicleDetail }
