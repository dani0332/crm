$(function()  {

    // Add new additional email
    $('#additional-email-add-btn').on('click', function(){
        alert('1');
    });

    // Add new additional mobile_no
    $('#additional-mobile-no-add-btn').on('click', function(){
        alert('2');
    });

    // Make additional email primary
    $('.additional-email-make-primary-btn').on('click', function(){
        if (confirm('Are you sure to make this primary email address?')) {
            var id = $(this).attr('data-record-id');
            $.ajax({
                url: '/customer-additional-contact/' + id + '/make-primary',
                method: 'POST',
                data: {
                    id: id,
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
                    id: id,
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