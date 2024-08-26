$(document).ready(function () {
  var maxField = 10; //Input fields increment limitation
  var addButton = $('#add_additional_btn'); //Add button selector
  var wrapper = $('#additional_info'); //Input field wrapper
  var fieldHTML = `
        <div class="item form-group">
            <div class="col">
                <span class="col-form-label col-md-6 col-sm-6"><b> Email Address (Optional)</b> </span>
                <input type="email" name="emails[]" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter email address">
            </div>
            <div class="col">
                <span class="col-form-label col-md-6 col-sm-6"><b>Phone number (Optional)</b> </span>
                <input type="text" name="phones[]"class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter 11 digit phone number. Example: 0563264418">
            </div>
            <a href="javascript:void(0);" class="remove_additional_btn" title="Add field"><img src="/image/remove-icon.png"/></a>
            
        </div> `;

  var x = 0; //Initial field counter is 1
  //Once add button is clicked
  $(addButton).click(function () {
    //Check maximum number of input fields
    if (x < maxField) {
      x++; //Increment field counter
      $(wrapper).append(fieldHTML); //Add field html
    }
  });

  //Once remove button is clicked
  $(wrapper).on('click', '.remove_additional_btn', function (e) {
    e.preventDefault();
    $(this).parent('div').remove(); //Remove field html
    x--; //Decrement field counter
  });
});
