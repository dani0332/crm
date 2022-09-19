$(function()  {

    // Add new additional contact modal
    $('#additional-contact-add-btn').on('click', function(){
        $('#customer-additional-contact-add-modal').modal({ show: true });
    });

    // Make additional email primary
    $('.additional-email-make-primary-btn').on('click', function(){
        if (confirm('Are you sure to make this primary email address?')) {
            var id = $(this).attr('data-record-id');
            var quote_id = $(this).attr('data-quote-id');
            var key = $(this).attr('data-key');
            var value = $(this).attr('data-value');
            var quote_type = $(this).attr('data-quote-type');
            $.ajax({
                url: '/customer-additional-contact/' + id + '/make-primary',
                method: 'POST',
                data: {
                    quote_id: quote_id,
                    key: key,
                    value:value,
                    quote_type : quote_type,
                    _token: $('input[name=_token]').val(),
                },
                success: function (data) {
                    
                },
            });
        } else {
            return false;
        }
    });

    // Delete additional email
    $('.additional-contact-delete-btn').on('click', function(){
        if (confirm('Are you sure to delete?')) {
            var id = $(this).attr('data-record-id');
            $.ajax({
                url: '/customer-additional-contact/' + id + '/delete',
                method: 'POST',
                data: {
                    _token: $('input[name=_token]').val(),
                },
                success: function (data) {
                    
                },
            });
        } else {
            return false;
        }
    });

    // On Add button click do validate Email/MobileNo
    $('#additional-contact-modal-add-btn').on('click', function(){
        var additional_contact_type_val = $('#additional_contact_type').val();
        var additional_contact_val = $('#additional_contact').val();
        var additional_mobile_no_reg_exp = new RegExp('[a-zA-Z]');

        if(additional_contact_type_val == 'email' && is_valid_email(additional_contact_val) === false) {
            validationDivText('#additional-contact-modal-validation-msg', 'Please enter a valid email address.');
            return false;
        }
        if(additional_contact_type_val == 'mobile_no' && (additional_contact_val == '' || additional_mobile_no_reg_exp.test(additional_contact_val))) {
            validationDivText('#additional-contact-modal-validation-msg', 'Please enter a valid  mobile number.');
            return false;
        }
        else {
            validationDivText('#additional-contact-modal-validation-msg', '');
        }
    });

    function is_valid_email(email) {
        var email_regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return email_regex.test(email);
    }

    function validationDivText(id, text) {
        $(id).text(text);
    }

});