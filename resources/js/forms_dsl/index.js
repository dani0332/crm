import leadAttachment from "./lead_attachment";
import ftcDetail from "./lead_ftc_detail";
import policyHolderDetail from "./policy_holder";
import vehicleDetail from "./vehicle_detail"
import leadRequest from "./car_requests";
import insuranceDetail from "./car_insurance_coverage"
import vehicleSubform from "./vehicle-subform";
import ftcHistory from "./ftc_history";
import carQuoteKycStatus from "./car_quote_kyc_status";

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
        case 'carQuoteKycStatus':
            return carQuoteKycStatus.getForm()
    }
}

const getFormObjForDraw = (obj) => {
    const { form  } = obj
    let formObj = getDSLForm( { form })
    return formObj
}

export  { getFormObjForDraw, getDSLForm, leadAttachment, ftcDetail, policyHolderDetail, vehicleDetail }
