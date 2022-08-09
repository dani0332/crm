$(document).ready(function() {

    // insurer onchange populate plans
    $("#insurance_provider_id").on("change", function (e) {
        $(".loader").show();
        $.ajax({
            url: '/insurance-provider-plans?insuranceProviderId=' + $("#insurance_provider_id option:selected").val() + '&quoteUuId=' + $("#car_quote_uuid").val(),
            type: 'get',
            success: function(response) {
                var carPlan = $("#car_plan_id").empty();
                var repairTypeComp = $('#repair_type_comp').val();
                changeFieldBgColor("#car_plan_id", "#ced4da");
                changeFieldBgColor("#car_plan_id", "");
                for (let index = 0; index < response.length; index++) {
                    const element = response[index];
                    var repairType = element.repair_type == repairTypeComp ? "NON-AGENCY" : element.repair_type;
                    carPlan.append('<option value="' + element.id + '">' + element.text + ' (' + repairType + ')</option>');
                }
                if(response.length == 0) {
                    carPlan.append('<option value="">Select Plan</option>');
                }
                $(".loader").hide();
            },
        });
    });

    $("#car-create-quote-form").submit(function (e) {
        if (!$('#insurance_provider_id').val() || !$('#car_plan_id').val() || !$('#actual_premium').val() || 
        !$('#car_value').val() || !$('#excess').val()) {
            validationDivText("#error-car-create-quote-form", "Please fill all the fields");
            return false;
        } else {
            validationDivText("#error-car-create-quote-form", "");
        }
    });

    function validationDivText(id, text) {
        $(id).text(text);
    }

    function changeFieldBgColor(id, color) {
        $(id).animate({ backgroundColor: color }).delay(1);
    }

});