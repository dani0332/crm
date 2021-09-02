<!DOCTYPE html>
<html>
<body>
    <p>Hi {{$first_name}} {{$last_name}}</p>
    <p>Thank you for sending us the documents required to start your insurance policy.
In order to proceed further and issue your policy document, we now need you to review the information for accuracy and confirm all is in order by clicking on the
"I confirm the details" button below.
</p>
<p>**Please note that any discrepancy with the below may invalidate your policy.**</p>
<h2 class="line_30">Section 1: Policy Holder & Vehicle Information</h2>
    <table class="countries_list">
        <tbody>
            <tr>
                <td>Name:</td>
                <td class="fs15 fw700 text-right">{{$first_name}} {{$last_name}}</td>
            </tr>
            <tr>
                <td>Nationality:</td>
                <td class="fs15 fw700 text-right">{{$nationality_id['text'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Date of Birth:</td>
                <td class="fs15 fw700 text-right">-</td>
            </tr>
            <tr>
                <td>UAE Years driving:</td>
                <td class="fs15 fw700 text-right">{{$uae_license_held_for_id['text'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Year of manufacture:</td>
                <td class="fs15 fw700 text-right">{{$Year_of_manufacture}}</td>
            </tr>
            <tr>
                <td>Date of first registration:</td>
                <td class="fs15 fw700 text-right">-</td>
            </tr>
            <tr>
                <td>Model:</td>
                <td class="fs15 fw700 text-right">{{$car_model_id['text'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Make:</td>
                <td class="fs15 fw700 text-right">{{$car_make_id['text'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Emirate of Registration:</td>
                <td class="fs15 fw700 text-right">{{$emirate_of_registration_id['text'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Declared years of no claims:</td>
                <td class="fs15 fw700 text-right">{{$claim_history_id['text'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Specs:</td>
                <td class="fs15 fw700 text-right">-</td>
            </tr>
            <tr>
                <td>Engine Capacity:</td>
                <td class="fs15 fw700 text-right">{{$vehicle_detail_id['engine_capacity'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Cylinders:</td>
                <td class="fs15 fw700 text-right">{{$vehicle_detail_id['cylinder'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Current Cover:</td>
                <td class="fs15 fw700 text-right">-</td>
            </tr>
            <tr>
                <td>Chassis Number:</td>
                <td class="fs15 fw700 text-right">{{$vehicle_detail_id['chassis_number'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Engine Number:</td>
                <td class="fs15 fw700 text-right">{{$vehicle_detail_id['engine_number'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Color of the vehicle:</td>
                <td class="fs15 fw700 text-right">{{$vehicle_detail_id['engine_number'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Seating Capacity:</td>
                <td class="fs15 fw700 text-right">{{$vehicle_detail_id['seating_capacity'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Vehicle Modified:</td>
                <td class="fs15 fw700 text-right">{{$vehicle_detail_id['vehicle_modified'] ?? ''}}</td>
            </tr>
        </tbody>
    </table>
    <h2 class="line_30">Section 2: Insurance Coverage Information</h2>
    <table class="countries_list">
        <tbody>
            <tr>
                <td>Policy start date:</td>
                <td class="fs15 fw700 text-right">{{$insurance_coverage['start_date'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Insurance Company:</td>
                <td class="fs15 fw700 text-right">{{$insurance_coverage['insurance_company_id']['name'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Sum Insured:</td>
                <td class="fs15 fw700 text-right">-</td>
            </tr>
            <tr>
                <td>Excess:</td>
                <td class="fs15 fw700 text-right">{{$insurance_coverage['excess'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Premium/Price:</td>
                <td class="fs15 fw700 text-right">{{$insurance_coverage['premium_price'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Ancillary Excess:(Applicable only to HPV or subjected to specific make & model)</td>
                <td class="fs15 fw700 text-right">{{$insurance_coverage['ancillary_excess'] ?? ''}}</td>
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
                <td class="fs15 fw700 text-right">{{$insurance_coverage['personal_accident_benefit'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Breakdown recovery:</td>
                <td class="fs15 fw700 text-right">{{$insurance_coverage['breakdown_recovery'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Off-road cover (for 4X4 only):</td>
                <td class="fs15 fw700 text-right">{{$insurance_coverage['off_road_cover'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Rent-a-car:</td>
                <td class="fs15 fw700 text-right">{{$insurance_coverage['rend_a_car'] ?? ''}}</td>
            </tr>
            <tr>
                <td>Geographical Area:</td>
                <td class="fs15 fw700 text-right">{{$insurance_coverage['geographical_area'] ?? ''}}</td>
            </tr>
        </tbody>
    </table>
   <p>A driver below 25 years of age must be declared Young/Novice Driver Clause: (10% of claim amount for drivers below the age of 25 or drivers holding less than one year UAE Driving Licence  who- unless convertible DL is known to have driven the car during the accident</p>
   <p>Please note that the terms contained within the attached quote supersede all others previously issued, and will form the basis of your insurance policy/contract. Please therefore read these thoroughly to ensure that everything is in accordance with your requirements and as per your expectations<p>
    <p>We look forward to hearing from you and to issuing your insurance documents as soon as possible.</p>
</body>
</html>
