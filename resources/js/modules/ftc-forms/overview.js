import React, { useState, useEffect } from "react";
import { useParams } from "react-router-dom";
import { session } from "../../utils";
import  { ScaleLoader } from "react-spinners";
import { dispatchPromise } from "../../sagas";
import {  useDispatch } from 'react-redux';
import moment from "moment";

export default function Overview(props) {

    const dispatch = useDispatch()
    const [state, setState ] = useState({ loader : false , data: {} });
    useEffect(() => {
        dispatchPromise({
            dispatch: dispatch,
            options: {
                type: 'SEND_REQUEST',
                request: { url: `/form/car_quote_request/${props?.filter?.car_quote_id}`}
            }
        }).then((response) => {
            setState({loader: false, data: response?.data})
        }).catch(error => {});

    }, [props]);



    return(

        <div className="row">
             {state?.loader === true && <div className="sweet-loading"><ScaleLoader color={'#000000'} loading={true} size={150} /></div> }
        <div className="col-md-12">
        <div className="x_panel">
            <div className="x_title">
                <h2>Car Request <small>Overivew</small></h2>
                <div className="clearfix"></div>
            </div>
            <div className="x_content">
                <div className="clearfix"></div>
        <div className="offset-md-2 col-md-7 hidden-small">
            <h2 class="line_30">Policy Holder Detail</h2>
            <table class="countries_list">
                <tbody>
                    <tr>
                        <td>Name:</td>
                        <td class="fs15 fw700 text-right">{state.data?.first_name} {state.data?.last_name}</td>
                    </tr>
                    <tr>
                        <td>Nationality:</td>
                        <td class="fs15 fw700 text-right">{state.data?.nationality_id?.text}</td>
                    </tr>
                    <tr>
                        <td>Date of Birth:</td>
                        <td class="fs15 fw700 text-right">{moment(state.data?.dob).format('dddd, MMMM Do YYYY')}</td>
                    </tr>
                    <tr>
                        <td>UAE Years driving:</td>
                        <td class="fs15 fw700 text-right">{state.data?.uae_license_held_for_id?.text}</td>
                    </tr>
                    </tbody>
                </table>
            <h2 class="line_30">Status</h2>
            <table class="countries_list">
                <tbody>
                    <tr>
                        <td>FTC status:</td>
                        <td class="fs15 fw700 text-right">{state.data?.quote_status_id?.text}</td>
                    </tr>
                    {/* <tr>
                        <td>KYC status:</td>
                        <td class="fs15 fw700 text-right">{state?.data?.kyc_status_id?.text}</td>
                    </tr>
                    <tr>
                        <td>AML status:</td>
                        <td class="fs15 fw700 text-right">{ state.data?.aml_status ? state.data.aml_status : "Pending" }</td>
                    </tr> */}
                    </tbody>
                </table>
            </div>
        </div></div></div></div>
        )
 }
