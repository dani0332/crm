

import React, { useEffect, useState  } from "react";
import { confirmAlert } from 'react-confirm-alert'; // Import
import 'react-confirm-alert/src/react-confirm-alert.css'; //
import { session } from "../../utils";
import  { ScaleLoader } from "react-spinners";
import { dispatchPromise } from "../../sagas";
import {  useDispatch } from 'react-redux';
import { getLocalStorage, setLocalStorage } from "../../utils";

export default function KycAMLForm(props) {

    const { form : { filter} } = props
    const dispatch = useDispatch()
    const { role } = session()
    const [state, setState ] = useState({ loader : false , found: 0, matches: 0 });

    const verifyAml = () => {
        setState({ loader: true, found: 0, matches: 0 })
        dispatchPromise({
            dispatch: dispatch,
            options: {
                type: 'SEND_REQUEST',
                request: { url: `/form/kyc_logs/?filter={"quote_request_id":"${filter.car_quote_id}"}`}
            }
        }).then((response) => {
                if(response?.data?.length > 0) {
                    const resp = response.data[0]
                    if(resp.results_found > 0){
                        setState({ loader: false, found: 1, matches: resp.results_found })
                    }else{
                        setState({ loader: false, found: 2, matches: resp.results_found })
                    }
                }else {
                    setState({ loader: false, found: 2, matches: 0 })
                }
        }).catch(error => {setState({ loader: false, found: 0 })});

    }

    const approveAml = (status) => {
        confirmAlert({
            title: status,
            message: 'Are you sure to  '+status+' AML?',
            buttons: [
              {
                label: 'Yes',
                onClick: async () => {
                    dispatchPromise({
                        dispatch: dispatch,
                        options: {
                            type: 'SEND_REQUEST',
                            request: { url: `/form/car_quote_aml_status`, method: 'POST', body: JSON.stringify( { status: status, car_quote_id: filter.car_quote_id  } ) }
                        }
                    }).then((response) => {
                        const storage = getLocalStorage('car_request_snap')
                        storage.aml_status = status
                        setLocalStorage('car_request_snap', storage)
                        setState({ loader: false, found: 3 })
                    }).catch(error => {});
                }
              },
              {
                label: 'No',
                onClick: () => {}
              }
            ]
        });
    }

    const viewDetail = () => {
        window.open("/kyc/aml/1/details/"+filter.car_quote_id, '_blank');
    }

    const { kyc_status_id, ...rest } = getLocalStorage('car_request_snap')
    const amlStatus = getLocalStorage('car_request_snap')?.aml_status || "";

    if(amlStatus != null &&  amlStatus.toLowerCase() === 'approved' || amlStatus.toLowerCase() === 'rejected') {
        return(
            <div className="row">
                <div className="col-md-12">
                <div className="x_panel">
                    <div className="x_title">
                        <h2>AML Process <small>Verify AML Detail</small></h2>
                        <div className="clearfix"></div>
                    </div>
                    <div className="x_content">
                        <div className="clearfix"></div>
                        <table className="countries_list" style={{width: "25%"}}>
                            <tbody>
                                <tr>
                                <td><span className="badge badge-success">AML has been {amlStatus}.</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
            </div>
        )
    }

    return(
        <div className="row">
           {state?.loader === true && <div className="sweet-loading"><ScaleLoader color={'#000000'} loading={true} size={150} /></div> }
           <div className="col-md-12">
                <div className="x_panel">
                    <div className="x_title">
                    <a href="javascript:void(0)" onClick={viewDetail} className="btn-link">Verify AML Detail <i class="fa fa-angle-right"></i></a>
                        <div className="clearfix"></div>
                    </div>
                    <div className="x_content">
                        <div className="clearfix"></div>
                        {state?.found === 1 &&
                            <table className="countries_list">
                            <tbody>
                                <tr>
                                    <td>Found Matches</td>
                                    <td className="fs15 fw700 text-right"></td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="left" style={{paddingTop: 16}}>

                                        <button type="button" class="btn btn-round btn-success btn-sm" onClick={viewDetail}>View Detail</button>
                                        { rest?.quote_status_id && rest?.quote_status_id === 11 && role === "pa" &&
                                        <span>
                                            <button type="button" class="btn btn-round btn-danger btn-sm" onClick={()=>approveAml('Rejected')}>AML Failed</button>
                                            <button type="button" class="btn btn-round btn-primary btn-sm" onClick={()=>approveAml('Approved')}>AML Cleared</button>
                                        </span>
                                        }
                                    </td>
                                </tr>
                            </tbody>
                        </table> }
                        {state?.found === 2 &&
                            <table className="countries_list" style={{width: "25%"}}>
                            <tbody>
                                <tr>
                                    <td>No Matches Found</td>
                                </tr>
                                { rest?.quote_status_id && rest?.quote_status_id === 11 && role === "pa" &&
                                <tr>
                                    <td  align="left" style={{paddingTop: 16}}><button type="button" className="btn btn-round btn-success" onClick={()=>approveAml('Approved')} >AML Check Cleared</button></td>
                                </tr>
                                }

                                { rest?.quote_status_id && rest?.quote_status_id !== 11 && role === "pa" &&
                                    <tr>
                                     <td  align="left" style={{paddingTop: 16}}><span className="badge badge-success">KYC has not cleared.</span></td>
                                    </tr>
                                }
                            </tbody>
                        </table> }
                        {state?.found === 3 &&
                            <table className="countries_list" style={{width: "25%"}}>
                            <tbody>
                                <tr>
                                    <td><span className="badge badge-success">AML has been verified.</span></td>
                                </tr>
                            </tbody>
                        </table> }
                        <div>
                            { state?.loader === false && state?.found === 0 && <button type="button" className="btn btn-round btn-success" onClick={verifyAml}> Verify AML</button> }
                        </div>
                    </div>
                </div>
            </div>
       </div>
    )
}
