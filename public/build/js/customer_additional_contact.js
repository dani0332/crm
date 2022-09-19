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
        var additional_contact_type = $('#additional_contact_type').val();
        var additional_contact = $('#additional_contact').val();
        var additional_mobile_no_reg_exp = new RegExp('[a-zA-Z]');

        if(additional_contact_type == 'email' && is_valid_email(additional_contact) === false) {
            $('#additional-contact-modal-validation-msg').text('Please enter a valid email address.');
            return false;
        }
        if(additional_contact_type == 'mobile_no' && (additional_contact == '' || additional_mobile_no_reg_exp.test(additional_contact))) {
            $('#additional-contact-modal-validation-msg').text('Please enter a valid mobile number.');
            return false;
        }
        else {
            $('#additional-contact-modal-validation-msg').text('');
        }
    });

    function is_valid_email(email) {
        var EmailRegex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return EmailRegex.test(email);
    }

});