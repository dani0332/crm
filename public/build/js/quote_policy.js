
$.getScript("../../build/js/helper.js");

$(document).ready(function() {

    changeElementVisibility(["update-quote-policy-btn","cancel-quote-policy-btn"], true, false);
    disabledFormFields("#update-quote-policy-form :input", true);
    disabledButton("#edit-quote-policy-btn", false);
    $("#edit-quote-policy-btn").click(function(){
        $("#edit-quote-policy-btn").hide();
        changeElementVisibility(["update-quote-policy-btn","cancel-quote-policy-btn"], true, true);
        disabledFormFields("#update-quote-policy-form :input", false);
        validationDivText("#error-quote-policy-form", "");
    });
    $("#cancel-quote-policy-btn").click(function(){
        $("#edit-quote-policy-btn").show();
        changeElementVisibility(["update-quote-policy-btn","cancel-quote-policy-btn"], true, false);
        disabledFormFields("#update-quote-policy-form :input", true);
        disabledButton("#edit-quote-policy-btn", false);
        validationDivText("#error-quote-policy-form", "");
    });
    validationDivText("#error-quote-policy-form", "");
    $("#update-quote-policy-form").submit(function (e) {
        if(!$('#quote_policy_number').val() || !$('#quote_policy_issuance_date').val() 
            || !$('#quote_policy_start_date').val() || !$('#quote_policy_expiry_date').val() 
            || !$('#quote_premium').val()) {
                validationDivText("#error-quote-policy-form", "All fields are required");
                return false;
        } else {
            var startDate = convertStringToDate($("#quote_policy_start_date").val());
            var expiryDate = convertStringToDate($("#quote_policy_expiry_date").val());
            if(startDate >= expiryDate) {
                validationDivText("#error-quote-policy-form", "Expiry date should be greater than Start Date");
                return false;
            }
            validationDivText("#error-quote-policy-form", "");
            return true;
        }
    });

    function validationDivText(id, text)
    {
        $(id).text(text);
    }
    function convertStringToDate(dateVal)
    {
        var datePart = dateVal.split('-');
        return new Date(datePart[2], datePart[1] - 1, datePart[0]);
    }
    function disabledFormFields(id, flag)
    {
        $(id).prop("disabled", flag);
    }
    function disabledButton(id, flag)
    {
        $(id).prop("disabled", flag);
    }
});