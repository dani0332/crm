import React from 'react';
import { useParams } from 'react-router-dom';
import { confirmAlert } from 'react-confirm-alert'; // Import
import 'react-confirm-alert/src/react-confirm-alert.css'; // Import css
import { session, capitalizeFirstLetter } from '../../utils';
import moment from 'moment';

export default function ReviewSend(props) {
  const { id } = useParams();
  const { dispatch } = props;
  const { role } = session();
  const submit = async () => {
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
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                data: JSON.stringify(props.data),
                car_quote_id: id,
                status: 'FTC Sent',
              }),
            };
            await fetch('/form/ftc_history', request);
            dispatch({ type: 'ftcHistory' });
          },
        },
        {
          label: 'No',
          onClick: () => {},
        },
      ],
    });
  };
  const { data } = props;
  let reviewSend = []
  if(role === 'advisor' || role === 'oe') {
    reviewSend.push(<button type='submit' className='btn btn-success' onClick={submit} > Review & Send </button>) 
  }

  return (
    <div className='row'>
      <div className='col-md-12'>
        <div className='x_panel'>
          <div className='x_title'>
            <h2>
              Review & Send <small>Preview FTC Email</small>
            </h2>
            <div className='clearfix'></div>
          </div>
          <div className='x_content'>
            <div className='clearfix'></div>
            <div className='offset-md-2 col-md-7 hidden-small'>
              {data?.vehicle_detail_id && data?.insurance_coverage && (
                <div className='pull-right'>
                  {reviewSend}
                </div>
              )}
              <h2 className='line_30'>Policy Holder & Vehicle Information</h2>
              <table className='countries_list'>
                <tbody>
                  <tr>
                    <td>Name:</td>
                    <td className='fs15 fw700 text-right'>
                      {capitalizeFirstLetter(data?.first_name)} {capitalizeFirstLetter(data?.last_name)}
                    </td>
                  </tr>
                  <tr>
                    <td>Nationality:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.nationality_id?.text}
                    </td>
                  </tr>
                  <tr>
                    <td>Date of Birth:</td>
                    <td className='fs15 fw700 text-right'>
                      {moment(data?.dob).format('YYYY/MM/DD').toString()}
                    </td>
                  </tr>
                  <tr>
                    <td>UAE Years driving:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.uae_license_held_for_id?.text}
                    </td>
                  </tr>
                  <tr>
                    <td>Year of manufacture:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.Year_of_manufacture}
                    </td>
                  </tr>
                  <tr>
                    <td>Date of first registration:</td>
                    { data?.date_first_registration && 
                      <td className='fs15 fw700 text-right'>
                        {moment(data.date_first_registration)
                          .format('YYYY/MM/DD')
                          .toString()}
                      </td>
                    }
                  </tr>
                  <tr>
                    <td>Model:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.car_model_id?.text}
                    </td>
                  </tr>
                  <tr>
                    <td>Make:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.car_make_id?.text}
                    </td>
                  </tr>
                  <tr>
                    <td>Emirate of Registration:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.emirate_of_registration_id?.text}
                    </td>
                  </tr>
                  <tr>
                    <td>Declared years of no claims:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.claim_history_id?.text}
                    </td>
                  </tr>
                  <tr>
                    <td>Specs:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.vehicle_detail_id?.specs}
                    </td>
                  </tr>
                  <tr>
                    <td>Engine Capacity:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.vehicle_detail_id?.engine_capacity}
                    </td>
                  </tr>
                  <tr>
                    <td>Cylinders:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.vehicle_detail_id?.cylinder}
                    </td>
                  </tr>
                  <tr>
                    <td>Current Cover:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.vehicle_detail_id?.current_cover}
                    </td>
                  </tr>
                  <tr>
                    <td>Chassis Number:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.vehicle_detail_id?.chassis_number}
                    </td>
                  </tr>
                  <tr>
                    <td>Engine Number:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.vehicle_detail_id?.engine_number}
                    </td>
                  </tr>
                  <tr>
                    <td>Color of the vehicle:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.vehicle_detail_id?.vehicle_color}
                    </td>
                  </tr>
                  <tr>
                    <td>Seating Capacity:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.vehicle_detail_id?.seating_capacity}
                    </td>
                  </tr>
                  <tr>
                    <td>Vehicle Modified:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.vehicle_detail_id?.vehicle_modified}
                    </td>
                  </tr>
                </tbody>
              </table>
              <h2 className='line_30'>Insurance Coverage Information</h2>
              <table className='countries_list'>
                <tbody>
                  <tr>
                    <td>Policy start date:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.insurance_coverage?.start_date}
                    </td>
                  </tr>
                  <tr>
                    <td>Insurance Company:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.plan_id?.provider_id?.text}
                    </td>
                  </tr>
                  <tr>
                    <td>Sum Insured:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.insurance_coverage?.sum_insured}
                    </td>
                  </tr>
                  <tr>
                    <td>Excess:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.insurance_coverage?.excess}
                    </td>
                  </tr>
                  <tr>
                    <td>Premium/Price:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.insurance_coverage?.premium_price}
                    </td>
                  </tr>
                  <tr>
                    <td>
                      Ancillary Excess:(Applicable only to HPV or subjected to
                      specific make & model)
                    </td>
                    <td className='fs15 fw700 text-right'>
                      {data?.insurance_coverage?.ancillary_excess}
                    </td>
                  </tr>
                  <tr>
                    <td>Repair type: </td>
                    <td className='fs15 fw700 text-right'>
                      {data?.insurance_coverage?.repair_type}
                    </td>
                  </tr>
                  <tr>
                    <td>Financed by (if any): </td>
                    <td className='fs15 fw700 text-right'>
                      {data?.insurance_coverage?.financed_by}
                    </td>
                  </tr>
                  <tr>
                    <td>Personal Accident Benefit:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.insurance_coverage?.personal_accident_benefit}
                    </td>
                  </tr>
                  <tr>
                    <td>Breakdown recovery:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.insurance_coverage?.breakdown_recovery}
                    </td>
                  </tr>
                  <tr>
                    <td>Off-road cover (for 4X4 only):</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.insurance_coverage?.off_road_cover}
                    </td>
                  </tr>
                  <tr>
                    <td>Rent-a-car:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.insurance_coverage?.rend_a_car}
                    </td>
                  </tr>
                  <tr>
                    <td>Geographical Area:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.insurance_coverage?.geographical_area}
                    </td>
                  </tr>
                  <tr>
                    <td colSpan='2'>
                      A driver below 25 years of age must be declared
                    </td>
                  </tr>
                  <tr>
                    <td colSpan='2'>
                      Young/Novice Driver Clause: (10% of claim amount for
                      drivers below the age of 25 or drivers holding less than
                      one year UAE Driving Licence who- unless convertible DL is
                      known to have driven the car during the accident
                    </td>
                  </tr>
                </tbody>
              </table>
              <h2 className='line_30'>KYC Detail</h2>
              <table className='countries_list'>
                <tbody>
                  <tr>
                    <td>Profession:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.car_quote_kyc?.profession}
                    </td>
                  </tr>
                  <tr>
                    <td>Name of Organization:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.car_quote_kyc?.organization}
                    </td>
                  </tr>
                  <tr>
                    <td>Designation:</td>
                    <td className='fs15 fw700 text-right'>
                      {data?.car_quote_kyc?.designation}
                    </td>
                  </tr>
                </tbody>
              </table>
              {data?.payment_detail?.mode === 'CC' &&
                data?.payment_detail?.method ===
                  'payments.insurancemarket.ae' && (
                  <div className='col-md-12'>
                    <h2 className='line_30'>Payments</h2>
                    <p>
                      Lastly, you may proceed with the credit card payment at
                      https://payments.insurancemarket.ae and send me the
                      6-digit code to issue the policy.
                    </p>
                  </div>
                )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
