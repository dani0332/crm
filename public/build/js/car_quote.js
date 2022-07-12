
$.getScript("../../build/js/helper.js");

$(document).ready(function() {

    $("#insurance_provider_id").on("change", function (e) {
        var insuranceProviderId = $("#insurance_provider_id option:selected").val();
        var quoteUuId = $("#car_quote_uuid").val();

        $.get("/insurance-provider-plans?insuranceProviderId=" + insuranceProviderId + "&quoteUuId=" + quoteUuId, function (data) {
            var carPlan = $("#car_plan_id").empty();
            changeFieldBgColor("#car_plan_id", "#ced4da");
            changeFieldBgColor("#car_plan_id", "");

            $.each(data, function (create, carPlanObj) {
                carPlan.append('<option value="' + carPlanObj.id + '">' + carPlanObj.text + ' (' + carPlanObj.repair_type + ')</option>');
            });
            if (data.length == 0) {
                carPlan.append('<option value="">Select Plan</option>');
            }
        });
    });

    $("#car-create-quote-form").submit(function (e) {
        var insuranceProviderId = $("#insurance_provider_id").val();
        var carPlanId = $("#car_plan_id").val();
        var premium = $("#premium").val();
        var value = $("#value").val();
        var excess = $("#excess").val();
    
        if (insuranceProviderId == "" || (carPlanId == "" || premium == "" || value == "" || excess == "")) {
            validationDivText("#error-car-create-quote-form", "Please fill all the fields");
            return false;
        } else {
            validationDivText("#error-car-create-quote-form", "");
        }
    
    });

    function validationDivText(id, text)
    {
        $(id).text(text);
    }

    function changeFieldBgColor(id, color)
    {
        $(id).animate({ backgroundColor: color }).delay(1);
    }

});