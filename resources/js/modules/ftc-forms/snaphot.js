import React, {  useRef, useReducer, useEffect } from "react";
import styled from "styled-components";
import SubNaV from "../../components/sub-nav";
import { useParams } from "react-router-dom";
import ManageListFormView from "../../components/list-view/manage-list-form-view";
import ReviewSend from "./review-send";
import KycForm from "./kyc-form";
import FtcForm from "./ftc-form";
import AssignUser from "./assign-user";
import { session } from "../../utils";
import Overview from "./overview";
const Styles = styled.div``;


function LeadSnapShot(props) {

let paramRef = useRef();
paramRef.current = useParams()

function reducer(state, action) {
    switch (action.type) {
      case 'document':
        return { form: 'leadAttachment' , view_mode: 'list',action_type: 'list', context: 'car_quote_snap',  filter: { car_quote_id: paramRef.current.id}};
      case 'policy':
        return { form: 'policyHolderDetail', view_mode: 'list',action_type: 'list', context: 'car_quote_snap', multi: false, filter: `/${paramRef.current.id}` };
      case 'vehicle':
        return  { form: 'vehicleDetail', view_mode: 'list',action_type: 'list' , context: 'car_quote_snap', multi: false, filter: `/${paramRef.current.id}` }
      case 'insurance':
        return  { form: 'carInsuranceDetail',  view_mode: 'list',action_type: 'list' , context: 'car_quote_snap', multi: false, filter: { car_quote_id: paramRef.current.id} }
      case 'ftcHistory':
        return  { form: 'ftcHistory',  view_mode: 'list',action_type: 'list' , context: 'car_quote_snap', filter: { car_quote_id: paramRef.current.id} }
      case 'email_template':
        return  { data: action.state, form: 'email_template' }
      case 'overview':
        return  { data: action.state, form: 'overview' }
    case 'kyc':
        return  { data: action.state, form: 'kyc' }
      case 'assign':
        return  { data: action.state, form: 'assign' }
    }
}

useEffect(() => {
    console.log('**************Snapshot--useEffect************')
}, []);

const { role, id, email } = session()
const [form, dispatch] = useReducer(reducer, { form: 'overview', view_mode: 'list',action_type: 'list' ,  context: 'car_quote_snap'});
console.log('**************Snapshot.js************')
console.log(form)

const leftNavList = [
    {
        icon:'fa fa-file-text-o',
        label:'Overview',
        active: 1,
        id: 8,
        data: 'overview'
    },
    {
        icon:'fa fa-upload',
        label:'Upload Documents',
        active: 0,
        id: 1,
        data: 'document'
    },
    {
        icon:'fa fa-file-text-o',
        label:'Policy Holder Detail',
        active: 0,
        id: 2,
        data: 'policy'
    },
   {
       icon:'fa fa-line-chart',
       label:'Vehicle Detail',
       active: 0,
       id: 3,
       data: 'vehicle'
   },
   {
    icon:'fa fa-line-chart',
    label:'Insurance Coverage Information',
    active: 0,
    id: 4,
    data: 'insurance'
 },
 {
    icon:'fa fa-line-chart',
    label:'FTC',
    active: 0,
    id: 5,
    data: 'ftcHistory'
 },
 {
    icon:'fa fa-line-chart',
    label:'Review & Send',
    active: 0,
    id: 6,
    data: 'email_template'
 },
 {
    icon:'fa fa-line-chart',
    label:'KYC',
    active: 0,
    id: 7,
    data: 'kyc'
 }
]

const onSelect = async (obj) => {

    const { data } = obj
    if(data === 'email_template') {
        const response = await fetch(`/form/car_quote_request/${paramRef.current.id}`, {
            method: 'GET',
            headers: {
            'Content-Type': 'application/json'
            }
        })
        const data =    await response.json()
        dispatch({ type: 'email_template', state: data?.data })
    }else{
        dispatch({ type: data })
    }
}
const formArr = []
switch(form?.form){
    case 'email_template':
        formArr.push(<ReviewSend dispatch={dispatch} data={form.data} />)
        break
    case 'ftcHistory':
        formArr.push( <FtcForm filter={{ car_quote_id: paramRef.current.id }} />)
        break
    case 'kyc':
        formArr.push( <KycForm filter={{ car_quote_id: paramRef.current.id }} />)
        break
    case 'assign':
        formArr.push( <AssignUser filter={{ car_quote_id: paramRef.current.id }}  email={email} />)
        break
    case 'overview':
        formArr.push( <Overview filter={{ car_quote_id: paramRef.current.id }} />)
        break
    default:
        formArr.push( <ManageListFormView form={form} />)
        break
}

return (
    <Styles>
      <div className="row x_panel">
            <div className="col-md-2 col-sm-2">
                <SubNaV listNav={leftNavList} onSelect={onSelect} />
            </div>
            <div className="col-md-10 col-sm-10 ">
                <div className="">
                    {formArr}
                </div>
            </div>
      </div>
    </Styles>
  );
}
export default LeadSnapShot;
