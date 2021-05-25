
$(document).ready(function(){
  $( "#datepicker" ).datepicker({ dateFormat: 'yy-mm-dd' });
  $( "#datepicker_2" ).datepicker({ dateFormat: 'yy-mm-dd' });

  $('#editor1').markdownEditor({
  preview: true,
  fullscreen:false,
  // imageUpload: true, // Activate the option
    
  // uploadPath: 'upload.php',
  onPreview: function (content, callback) {
    
  callback( marked(content) );
    
  }
    
  });

  $('#editor2').markdownEditor({
    
    preview: true,
    fullscreen:false,
    // imageUpload: true, // Activate the option
      
    // uploadPath: 'upload.php',
    onPreview: function (content, callback) {
      
    callback( marked(content) );
      
    }
      
    });


    $('#editor3').markdownEditor({
    
    preview: true,
    fullscreen:false,
    // imageUpload: true, // Activate the option
      
    // uploadPath: 'upload.php',
    onPreview: function (content, callback) {
      
    callback( marked(content) );
      
    }
      
    });
    
    $('#editor4').markdownEditor({
    
    preview: true,
    fullscreen:false,
    // imageUpload: true, // Activate the option
      
    // uploadPath: 'upload.php',
    onPreview: function (content, callback) {
      
    callback( marked(content) );
      
    }
      
  });
  $("#datatable").DataTable().destroy()
  $('#datatable').DataTable( {
      // "paging":   false,
      "ordering": false,
      "info":     false,
      'searching':false,
      "bLengthChange": false
  } );

  var table = $('.data-table').DataTable({
      
      ordering: false,
      info:     false,
      searching:false,
      bLengthChange: false,
      ajax: config.routes.partner_datatable_route,
      columns: [
          {data: 'id', name: 'id'},
          {data: 'name', name: 'name'},
          {data: 'name_ar', name: 'name_ar'},
          {data: "logo_image", name: "logo_image",
          render: function (data, type, row, meta) {
              var imgsrc = imagePath + data; // here data should be in base64 string
              return '<img class="img-responsive" src="' + imgsrc +'" alt="logo_image" height="40px" width="40px">';
          }},
          {data: 'action', name: 'action', orderable: false, searchable: false},
      ]
  });

  var table = $('.user-data-table').DataTable({
      ordering: false,
      info:     false,
      searching:false,
      bLengthChange: false,
      serverSide: true,
      ajax: config.routes.user_datatable_route,
      columns: [
          {data: 'id', name: 'id'},
          {data: 'name', name: 'name'},
          {data: 'email', name: 'email'},
          {data: 'action', name: 'action', orderable: false, searchable: false},
      ]
  });

  var table = $('.role-data-table').DataTable({
      ordering: false,
      info:     false,
      searching:false,
      bLengthChange: false,
      serverSide: true,
      ajax: config.routes.role_datatable_route,
      columns: [
          {data: 'id', name: 'id'},
          {data: 'name', name: 'name'},
          {data: 'action', name: 'action', orderable: false, searchable: false},
      ]
  });

  var table = $('.carqoute-data-table').DataTable({
      ordering: false,
      info:     false,
      searching:false,
      bLengthChange: false,
      serverSide: true,
      ajax: config.routes.carqoute_datatable_route,
      columns: [
          {data: "checkbox", name: "checkbox",
          render: function (data, type, row, meta) {
              var imgsrc = imagePath + data; // here data should be in base64 string
              return '<input type="checkbox" class="flat multicheckbox" name="row[]" data-id="'+row.id+'">';
          }},
          {data: 'id', name: 'id'},
          {data: 'car_value', name: 'car_value'},
          {data: 'is_synced', name: 'is_synced'},
          {data: 'device', name: 'device'},
          {data: 'code', name: 'code'},

      ]
  });

  $('#select_all_checkboxes').click(function(e){
    var isChecked = e.target.checked;
    
    if(isChecked === true){
      $( ".multicheckbox" ).each(function( index ) {
        $(this).prop('checked',true)
      });
    }else{
      $( ".multicheckbox" ).each(function( index ) {
        $(this).prop('checked',false)
      });
    }
  })

  $('#resubmit_api_carqoute').click(function(){
      $('#resubmit_api_carqoute').attr('disabled',true);
      var carQoutes = [];
      $( ".multicheckbox" ).each(function( index ) {
          if($(this).prop('checked')){
              var id = $(this).attr('data-id');
              carQoutes.push(id);
          }
      });
      if(carQoutes.length > 0){
          $.ajax({
                  url:config.routes.carqoute_resubmitap_route,
                  type: "post",
                  data: {car_qoutes:carQoutes,"_token":config._token},
                  success: function (response) {
                      $('#success_message').show();
                      $('#success_message').fadeIn().html('Resubmit Api Execution is successfull');
                      
                      $('.carqoute-data-table').DataTable().ajax.reload();

                      setTimeout(function() {
                          $('#resubmit_api_carqoute').attr('disabled',false);
                          $('#success_message').fadeOut("slow");
                      }, 3000 );


                      
                  },
                  error: function(jqXHR, textStatus, errorThrown) {
                  console.log(textStatus, errorThrown);
                      $('#error_message').show();
                          $('#error_message').fadeIn().html(errorThrown);
                      setTimeout(function() {
                          $('#resubmit_api_carqoute').attr('disabled',false);
                          $('#error_message').fadeOut("slow");
                      }, 3000 );
                  }
          });
      }
      else{
          $('#error_message').show();
              $('#error_message').fadeIn().html('Please select atleast one row');
          setTimeout(function() {
              $('#resubmit_api_carqoute').attr('disabled',false);
              $('#error_message').fadeOut("slow");
          }, 3000 );
      }
      
  })
  
})