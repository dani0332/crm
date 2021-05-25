<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('image/favicon.ico') }}">
    <title>@yield('title') | MyAlfredCrm</title>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <!-- Bootstrap -->
    <link href="{{ asset('vendors/bootstrap/dist/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />

    <!-- Font Awesome -->
    <link href="{{ asset('vendors/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet">
    <!-- NProgress -->
    <link href="{{ asset('vendors/nprogress/nprogress.css') }}" rel="stylesheet">
    <!-- bootstrap-wysiwyg -->
    <link href="{{ asset('vendors/google-code-prettify/bin/prettify.min.css') }}" rel="stylesheet">

    <!-- Custom styling plus plugins -->
    <link href="{{ asset('build/css/custom.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-bs/css/dataTables.bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css') }}" rel="stylesheet">
	  <link href="https://www.jquery-az.com/jquery/css/bootstrap-markdown-editor.css" rel="stylesheet">

    <!-- iCheck -->
	  <link href="{{ asset('vendors/iCheck/skins/flat/green.css') }}" rel="stylesheet">

    
  </head>

    <body class="nav-md">
    <div class="container body">
      <div class="main_container">
            @include('partials.sidebar')
            @include('partials.topnav')
            <div class="right_col" role="main">
                <div class="">
                    @yield('content')
                </div>
            </div>
           
            @include('partials.footer')

      </div>
    </div>   
    <!-- jQuery -->
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <!-- Bootstrap -->
   <script src="{{ asset('vendors/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <!-- FastClick -->
    <script src="{{ asset('vendors/fastclick/lib/fastclick.js') }}"></script>
    <!-- NProgress -->
    <script src="{{ asset('vendors/nprogress/nprogress.js') }}"></script>
    <!-- bootstrap-wysiwyg -->
    <script src="{{ asset('vendors/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
    <script src="{{ asset('vendors/jquery.hotkeys/jquery.hotkeys.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.1.3/ace.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/0.3.2/marked.min.js"></script>
    <script src="https://www.jquery-az.com/jquery/js/bootstrap-markdown-editor.js"></script>

    
    <script src="{{ asset('vendors/google-code-prettify/src/prettify.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons/js/buttons.flash.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-keytable/js/dataTables.keyTable.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-scroller/js/dataTables.scroller.min.js') }}"></script>
    <!-- iCheck -->
	  <script src="{{ asset('vendors/iCheck/icheck.min.js') }}"></script>
    <!-- Custom Theme Scripts -->
    <script src="{{ asset('build/js/custom.min.js') }}"></script>
    <script type="text/javascript">
      var imagePath ="{{ \Config::get('constants.azure_storage_url').'myrewards/' }}";
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
            ajax: "{{ route('partner.index') }}",
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
            ajax: "{{ route('users.index') }}",
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
            ajax: "{{ route('roles.index') }}",
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
            ajax: "{{ route('carqoutes.index') }}",
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
                        url:"{{ url('qoutes/carqoutes/resubmit_api') }}",
                        type: "post",
                        data: {car_qoutes:carQoutes,"_token": "{{ csrf_token() }}"},
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
    </script>
    </body>
</html>
