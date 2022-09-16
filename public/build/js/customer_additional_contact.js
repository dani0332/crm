$(function()  {

    // Add new additional contact
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

});