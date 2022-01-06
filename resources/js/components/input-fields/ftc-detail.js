import React from 'react';

export default function FTCDetail({ field }) {
  const data = JSON.parse(field?.value);
  return (
    <div className=' hidden-small'>
      <h2 className='line_30'>Policy Holder & Vehicle Information</h2>
      <table className='countries_list'>
        <tbody>
          <tr>
            <td>Name:</td>
            <td className='fs15 fw700 text-right'>
              {data?.first_name} {data?.last_name}
            </td>
          </tr>
          <tr>
            <td>Nationality:</td>
            <td className='fs15 fw700 text-right'>
              {data?.nationality_id.text}
            </td>
          </tr>
          <tr>
            <td>Date of Birth:</td>
            <td className='fs15 fw700 text-right'>-</td>
          </tr>
          <tr>
            <td>UAE Years driving:</td>
            <td className='fs15 fw700 text-right'>
              {data?.uae_license_held_for_id.text}
            </td>
          </tr>
          <tr>
            <td>Year of manufacture:</td>
            <td className='fs15 fw700 text-right'>
              {data.Year_of_manufacture}
            </td>
          </tr>
          <tr>
            <td>Date of first registration:</td>
            <td className='fs15 fw700 text-right'>-</td>
          </tr>
          <tr>
            <td>Model:</td>
            <td className='fs15 fw700 text-right'>{data?.car_model_id.text}</td>
          </tr>
          <tr>
            <td>Make:</td>
            <td className='fs15 fw700 text-right'>{data?.car_make_id.text}</td>
          </tr>
          <tr>
            <td>Emirate of Registration:</td>
            <td className='fs15 fw700 text-right'>
              {data?.emirate_of_registration_id.text}
            </td>
          </tr>
          <tr>
            <td>Declared years of no claims:</td>
            <td className='fs15 fw700 text-right'>
              {data?.claim_history_id.text}
            </td>
          </tr>
          <tr>
            <td>Specs:</td>
            <td className='fs15 fw700 text-right'>-</td>
          </tr>
          <tr>
            <td>Engine Capacity:</td>
            <td className='fs15 fw700 text-right'>
              {data?.vehicle_detail_id.engine_capacity}
            </td>
          </tr>
          <tr>
            <td>Cylinders:</td>
            <td className='fs15 fw700 text-right'>
              {data?.vehicle_detail_id.cylinder}
            </td>
          </tr>
          <tr>
            <td>Current Cover:</td>
            <td className='fs15 fw700 text-right'>-</td>
          </tr>
          <tr>
            <td>Chassis Number:</td>
            <td className='fs15 fw700 text-right'>
              {data?.vehicle_detail_id.chassis_number}
            </td>
          </tr>
          <tr>
            <td>Engine Number:</td>
            <td className='fs15 fw700 text-right'>
              {data?.vehicle_detail_id.engine_number}
            </td>
          </tr>
          <tr>
            <td>Color of the vehicle:</td>
            <td className='fs15 fw700 text-right'>
              {data?.vehicle_detail_id.vehicle_color}
            </td>
          </tr>
          <tr>
            <td>Seating Capacity:</td>
            <td className='fs15 fw700 text-right'>
              {data?.vehicle_detail_id.seating_capacity}
            </td>
          </tr>
          <tr>
            <td>Vehicle Modified:</td>
            <td className='fs15 fw700 text-right'>
              {data?.vehicle_detail_id.vehicle_modified}
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
            <td className='fs15 fw700 text-right'>-</td>
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
              Ancillary Excess:(Applicable only to HPV or subjected to specific
              make & model)
            </td>
            <td className='fs15 fw700 text-right'>
              {data?.insurance_coverage?.ancillary_excess}
            </td>
          </tr>
          <tr>
            <td>Repair type:</td>
            <td className='fs15 fw700 text-right'>-</td>
          </tr>
          <tr>
            <td>Financed by (if any):</td>
            <td className='fs15 fw700 text-right'>-</td>
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
        data?.payment_detail?.method === 'payments.insurancemarket.ae' && (
          <div className='col-md-12'>
            <h2 className='line_30'>Payments</h2>
            <p>
              Lastly, you may proceed with the credit card payment at
              https://payments.insurancemarket.ae and send me the 6-digit code
              to issue the policy.
            </p>
          </div>
        )}
    </div>
  );
}
