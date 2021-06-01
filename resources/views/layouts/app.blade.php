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
    <link href="{{ asset('build/style.css') }}" rel="stylesheet">
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
    <script>
        // global app configuration object
        var config = {
            routes: {
                partner_datatable_route: "{{ route('partner.index') }}",
                user_datatable_route:"{{ route('users.index') }}",
                role_datatable_route:"{{ route('roles.index') }}",
                carqoute_datatable_route:"{{ route('carqoutes.index') }}",
                carqoute_resubmitap_route:"{{ url('qoutes/carqoutes/resubmit_api') }}",
                healthqoute_datatable_route:"{{ route('healthqoutes.index') }}",
                reward_datatable_route:"{{ route('reward.index') }}",
                reward_categories_datatable_route:"{{ route('reward-categories.index') }}",
                reward_tags_datatable_route:"{{ route('reward-tags.index') }}",
                claim_datatable_route:"{{ route('claims.index') }}",
                typeofinsurance_datatable_route:"{{ route('typeofinsurance.index') }}",
                subtypeofinsurance_datatable_route:"{{ route('subtypeofinsurance.index') }}",
                claimsstatus_datatable_route:"{{ route('claimsstatus.index') }}",
                carrepaircoverage_datatable_route:"{{ route('carrepaircoverage.index') }}",
                carrepairtype_datatable_route:"{{ route('carrepairtype.index') }}",
                rentacar_datatable_route:"{{ route('rentacar.index') }}",
            },
            _token:"{{ csrf_token() }}",
            image_path:"{{ \Config::get('constants.azure_storage_url').'myrewards/' }}"
        };
    </script>
    <script src="{{ asset('build/js/customjs.js') }}"></script>
    </body>
</html>
