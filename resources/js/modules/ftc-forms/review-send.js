

import React, {   } from "react";
import { useParams } from "react-router-dom";
import { confirmAlert } from 'react-confirm-alert'; // Import
import 'react-confirm-alert/src/react-confirm-alert.css'; // Import css
import { session } from "../../utils";

export default function ReviewSend(props) {

    const { id } = useParams()
    const { dispatch } = props
    const { role } = session()
    const submit = async (obj) => {

        confirmAlert({
            title: 'Confirm to Send',
            message: 'Are you sure to do this.',
            buttons: [
              {
                label: 'Yes',
                onClick: async () => {

                    const request = {
                        method: 'POST',
                        headers: {
                        'Content-Type': 'application/json'
                        },
                        body : JSON.stringify( { data: JSON.stringify(props.data), car_quote_id : id , status: 'FTC Sent' } )
                    }
                    await fetch('/form/ftc_history', request)
                    dispatch( { type: 'ftcHistory'} )

                }
              },
              {
                label: 'No',
                onClick: () => {}
              }
            ]
          });
    }


    const { data } = props



    return(
        <div className="offset-md-2 col-md-7 hidden-small">
            { data?.vehicle_detail_id && data?.insurance_coverage &&
            <div className="pull-right">
             { role === 'advisor' && <button type="submit" className="btn btn-success" onClick={submit}>Review & Send</button> }
             </div>
            }
            <h2 class="line_30">Policy Holder & Vehicle Information</h2>
            <table class="countries_list">
                <tbody>
                    <tr>
                        <td>Name:</td>
                        <td class="fs15 fw700 text-right">{data?.first_name} {data?.last_name}</td>
                    </tr>
                    <tr>
                        <td>Nationality:</td>
                        <td class="fs15 fw700 text-right">{data?.nationality_id.text}</td>
                    </tr>
                    <tr>
                        <td>Date of Birth:</td>
                        <td class="fs15 fw700 text-right">-</td>
                    </tr>
                    <tr>
                        <td>UAE Years driving:</td>
                        <td class="fs15 fw700 text-right">{data?.uae_license_held_for_id.text}</td>
                    </tr>
                    <tr>
                        <td>Year of manufacture:</td>
                        <td class="fs15 fw700 text-right">{data?.Year_of_manufacture}</td>
                    </tr>
                    <tr>
                        <td>Date of first registration:</td>
                        <td class="fs15 fw700 text-right">-</td>
                    </tr>
                    <tr>
                        <td>Model:</td>
                        <td class="fs15 fw700 text-right">{data?.car_model_id.text}</td>
                    </tr>
                    <tr>
                        <td>Make:</td>
                        <td class="fs15 fw700 text-right">{data?.car_make_id.text}</td>
                    </tr>
                    <tr>
                        <td>Emirate of Registration:</td>
                        <td class="fs15 fw700 text-right">{data?.emirate_of_registration_id.text}</td>
                    </tr>
                    <tr>
                        <td>Declared years of no claims:</td>
                        <td class="fs15 fw700 text-right">{data?.claim_history_id.text}</td>
                    </tr>
                    <tr>
                        <td>Specs:</td>
                        <td class="fs15 fw700 text-right">-</td>
                    </tr>
                    <tr>
                        <td>Engine Capacity:</td>
                        <td class="fs15 fw700 text-right">{data?.vehicle_detail_id?.engine_capacity}</td>
                    </tr>
                    <tr>
                        <td>Cylinders:</td>
                        <td class="fs15 fw700 text-right">{data?.vehicle_detail_id?.cylinder}</td>
                    </tr>
                    <tr>
                        <td>Current Cover:</td>
                        <td class="fs15 fw700 text-right">-</td>
                    </tr>
                    <tr>
                        <td>Chassis Number:</td>
                        <td class="fs15 fw700 text-right">{data?.vehicle_detail_id?.chassis_number}</td>
                    </tr>
                    <tr>
                        <td>Engine Number:</td>
                        <td class="fs15 fw700 text-right">{data?.vehicle_detail_id?.engine_number}</td>
                    </tr>
                    <tr>
                        <td>Color of the vehicle:</td>
                        <td class="fs15 fw700 text-right">{data?.vehicle_detail_id?.vehicle_color}</td>
                    </tr>
                    <tr>
                        <td>Seating Capacity:</td>
                        <td class="fs15 fw700 text-right">{data?.vehicle_detail_id?.seating_capacity}</td>
                    </tr>
                    <tr>
                        <td>Vehicle Modified:</td>
                        <td class="fs15 fw700 text-right">{data?.vehicle_detail_id?.vehicle_modified}</td>
                    </tr>
                </tbody>
            </table>
            <h2 class="line_30">Insurance Coverage Information</h2>
            <table class="countries_list">
                <tbody>
                    <tr>
                        <td>Policy start date:</td>
                        <td class="fs15 fw700 text-right">{data?.insurance_coverage?.start_date}</td>
                    </tr>
                    <tr>
                        <td>Insurance Company:</td>
                        <td class="fs15 fw700 text-right">{data?.insurance_coverage?.insurance_company_id?.name}</td>
                    </tr>
                    <tr>
                        <td>Sum Insured:</td>
                        <td class="fs15 fw700 text-right">-</td>
                    </tr>
                    <tr>
                        <td>Excess:</td>
                        <td class="fs15 fw700 text-right">{data?.insurance_coverage?.excess}</td>
                    </tr>
                    <tr>
                        <td>Premium/Price:</td>
                        <td class="fs15 fw700 text-right">{data?.insurance_coverage?.premium_price}</td>
                    </tr>
                    <tr>
                        <td>Ancillary Excess:(Applicable only to HPV or subjected to specific make & model)</td>
                        <td class="fs15 fw700 text-right">{data?.insurance_coverage?.ancillary_excess}</td>
                    </tr>
                    <tr>
                        <td>Repair type:</td>
                        <td class="fs15 fw700 text-right">-</td>
                    </tr>
                    <tr>
                        <td>Financed by (if any):</td>
                        <td class="fs15 fw700 text-right">-</td>
                    </tr>
                    <tr>
                        <td>Personal Accident Benefit:</td>
                        <td class="fs15 fw700 text-right">{data?.insurance_coverage?.personal_accident_benefit}</td>
                    </tr>
                    <tr>
                        <td>Breakdown recovery:</td>
                        <td class="fs15 fw700 text-right">{data?.insurance_coverage?.breakdown_recovery}</td>
                    </tr>
                    <tr>
                        <td>Off-road cover (for 4X4 only):</td>
                        <td class="fs15 fw700 text-right">{data?.insurance_coverage?.off_road_cover}</td>
                    </tr>
                    <tr>
                        <td>Rent-a-car:</td>
                        <td class="fs15 fw700 text-right">{data?.insurance_coverage?.rend_a_car}</td>
                    </tr>
                    <tr>
                        <td>Geographical Area:</td>
                        <td class="fs15 fw700 text-right">{data?.insurance_coverage?.geographical_area}</td>
                    </tr>
                    <tr>
                        <td colSpan="2">A driver below 25 years of age must be declared</td>
                    </tr>
                    <tr>
                        <td colSpan="2">Young/Novice Driver Clause: (10% of claim amount for drivers below the age of 25 or drivers holding less than one year UAE Driving Licence  who- unless convertible DL is known to have driven the car during the accident</td>
                    </tr>
                </tbody>
            </table>
        </div>
    )
}
