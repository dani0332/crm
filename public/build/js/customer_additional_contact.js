$(function()  {

    // Add new additional contact modal
    $('#additional-contact-add-btn').on('click', function(){
        $('#customer-additional-contact-add-modal').modal({ show: true });
    });

    // Make additional email primary
    $('.additional-email-make-primary-btn').on('click', function(){
        if (confirm('Are you sure to make this primary email address?')) {
            $('.loader').show();
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
                    $('.loader').hide();
                    alert('Primary Contact Updated.');
                    location.reload(); 
                },
            });
        } else {
            return false;
        }
    });

    // Delete additional email
    $('.additional-contact-delete-btn').on('click', function(){
        if (confirm('Are you sure to delete?')) {
            $('.loader').show();
            var customer_id = $(this).attr('data-customer-additional-contact-id');
            $.ajax({
                url: '/customer-additional-contact/' + customer_id + '/delete',
                method: 'POST',
                data: {
                    _token: $('input[name=_token]').val(),
                },
                success: function (data) {
                    $('.loader').hide();
                    alert(data.data.message);
                    location.reload();
                },
            });
        } else {
            return false;
        }
    });

    // On Add button click do validate Email/MobileNo
    $('#additional-contact-modal-add-btn').on('click', function() {
        var additional_mobile_no_reg_exp = new RegExp('[a-zA-Z]');
        var additional_contact_type = $('#additional_contact_type').val();
        var additional_contact_val = $('#additional_contact').val();
        var quote_id = $(this).attr('data-quote-id');
        var quote_type = $(this).attr('data-quote-type');
        var customer_id = $(this).attr('data-customer-id');
        var contact_type_email_enum = $(this).attr('data-contact-type-email-enum');
        var contact_type_mobile_no_enum = $(this).attr('data-contact-type-mobile-no-enum');

        if(additional_contact_type == contact_type_email_enum && is_valid_email(additional_contact_val) === false) {
            validation_div_text('#additional-contact-modal-validation-msg', 'Please enter a valid email address.', 'red');
            return false;
        }
        if(additional_contact_type == contact_type_mobile_no_enum && (additional_contact_val == '' || additional_mobile_no_reg_exp.test(additional_contact_val))) {
            validation_div_text('#additional-contact-modal-validation-msg', 'Please enter a valid  mobile number.', 'red');
            return false;
        }
        else {
            $('.loader').show();
            validation_div_text('#additional-contact-modal-validation-msg', '', '');
            $.ajax({
                url: '/customer-additional-contact/add',
                method: 'POST',
                data: {
                    quote_id : quote_id,
                    customer_id : customer_id,
                    additional_contact_type: additional_contact_type,
                    additional_contact_val : additional_contact_val,
                    quote_type : quote_type,
                    _token: $('input[name=_token]').val(),
                },
                success: function (data) {
                    $('.loader').hide();
                    if(data.error && data.error.message) {
                        validation_div_text('#additional-contact-modal-validation-msg', data.error.message, 'red');
                    }
                    else if(data.data.message) {
                        alert('Contact Added.');
                        location.reload();
                    } else {
                        validation_div_text('#additional-contact-modal-validation-msg', 'There was an error!', 'red');
                    }
                },
            });
        }
    });

    function is_valid_email(email) {
        var email_regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return email_regex.test(email);
    }

    function validation_div_text(id, text, color) {
        $(id).text(text).attr('style', 'color:' + color);
    }

});