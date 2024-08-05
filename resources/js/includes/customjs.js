var selectedPkgIds = new Set();

$(document).ready(function () {
  $('#ebp_dob').datepicker({
    changeMonth: true,
    changeYear: true,
    dateFormat: 'dd-mm-yy',
    yearRange: '-80:+00',
  });

  $('.selectpicker').selectpicker();
  $('#datepicker').datepicker({ dateFormat: 'yy-mm-dd' });
  $('#datepicker_2').datepicker({ dateFormat: 'yy-mm-dd' });
  $('#transapp_start_date').datepicker({ dateFormat: 'yy-mm-dd' });
  $('#transapp_stop_date').datepicker({ dateFormat: 'yy-mm-dd' });
  $('#dtBasicExample').DataTable();
  $('.dataTables_length').addClass('bs-select');

  $('.js-example-basic-multiple').select2({
    placeholder: 'Select Permissions to assign against role',
    width: '100%',
    allowClear: true,
  });
  $(
    '.select-roles,#user-team-select,#user-product-select,#user-manager-select',
  ).select2({
    width: '100%',
    allowClear: true,
  });
  // TM Leads, AML
  $(
    '#enquiry_date, #allocation_date, #tmLeadsStartDate, #tmLeadsEndDate, #amlCreatedStartDate, #amlCreatedEndDate',
  ).datepicker({
    changeMonth: true,
    changeYear: true,
    dateFormat: 'yy-mm-dd',
    yearRange: '-20:+00',
  });

  $('#dob').datepicker({
    // TM Leads
    changeMonth: true,
    changeYear: true,
    dateFormat: 'yy-mm-dd',
    yearRange: '-80:+00',
  });
  $('input[type^=date]').datepicker({
    // TM Leads
    changeMonth: true,
    changeYear: true,
    dateFormat: 'yy-mm-dd',
    yearRange: '-80:+00',
  });

  if (window.location.href.indexOf('tmleads') > -1) {
    if (performance.navigation.type == 2) {
      location.reload(true);
    }
  }

  $('#next_followup_date_field > #next_followup_date').daterangepicker({
    // TM Leads
    timePicker: true,
    singleDatePicker: true,
    timePicker24Hour: true,
    locale: {
      format: 'YYYY-MM-DD HH:mm:ss',
    },
  });
  $('#search-valuation').validate({
    rules: {
      carmake: {
        required: true,
      },
      carmodel: {
        required: true,
      },
      cartrim: {
        required: true,
      },
      yom: {
        required: true,
        digits: true,
      },
    },
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback').attr('style', 'font-size: 17px');
      element.closest('.form-group').append(error);
    },
    highlight: function (element, errorClass, validClass) {
      $(element).addClass('is-invalid');
    },
    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass('is-invalid');
    },
  });
  $('#calculateValuation').click(function () {
    if ($('#search-valuation').valid()) {
      $('#result').hide();
      var carMake = $('#car_make_value option:selected').val();
      var carModel = $('#car_model_value option:selected').val();
      var carTrim = $('#car_trim_value option:selected').val();
      var yom = $('#yom').val();
      $.ajax({
        url: config.routes.valuation_api_route + 'get-vehicle-value',
        type: 'post',
        headers: {
          'x-api-token': config.routes.valuation_api_token,
        },
        data: { carModelDetailId: carTrim, yearOfManufacture: yom },
        success: function (response) {
          var html = '';
          response.forEach(element => {
            html +=
              '<tr><td>' +
              element.providerName +
              '</td><td>' +
              Number(element.carValue) +
              '</td><td>' +
              Number(element.carValueUpperLimit) +
              '</td><td>' +
              Number(element.carValueLowerLimit) +
              '</td></tr>';
          });
          $('#result table tbody').html(html);
          $('#result').show();
        },
        error: function (jqXHR, textStatus, errorThrown) {
          if (jqXHR.responseJSON.msg == 'Car Trim Not found') {
            $('#error').show();
            $('#error').text('Cannot calculate depreciation without trim');
            $('#error').hide().delay(5000).fadeIn(400);
          } else {
            $('#error').show();
            $('#error').text(jqXHR.responseJSON.msg);
            $('#error').hide().delay(5000).fadeIn(400);
          }
        },
      });
    }
  });
  $('#reset').click(function () {
    $('#result').hide();
    $('#carValue').text('');
    $('#uLimit').text('');
    $('#lLimit').text('');
    $('#car_model_value').find('option').not(':first').remove();
    $('#car_trim_value').find('option').not(':first').remove();
    $('#yom').val(new Date().getFullYear() - 1);
  });
  $('#editor1').markdownEditor({
    preview: true,
    fullscreen: false,
    onPreview: function (content, callback) {
      callback(marked(content));
    },
  });

  $('#editor2').markdownEditor({
    preview: true,
    fullscreen: false,
    onPreview: function (content, callback) {
      callback(marked(content));
    },
  });

  $('#editor3').markdownEditor({
    preview: true,
    fullscreen: false,
    onPreview: function (content, callback) {
      callback(marked(content));
    },
  });

  $('#editor4').markdownEditor({
    preview: true,
    fullscreen: false,
    onPreview: function (content, callback) {
      callback(marked(content));
    },
  });
  $('#datatable').DataTable().destroy();
  $('#datatable').DataTable({
    // "paging": false,
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    scrollX: true,
  });

  $('.datatable-show').DataTable().destroy();
  $('.datatable-show').DataTable({
    // "paging": false,
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    scrollX: true,
  });

  $('#addon-datatable').DataTable().destroy();
  $('#addon-datatable').DataTable({
    // "paging": false,
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    scrollX: true,
  });

  var usersDataTable = $('.user-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: {
      url: config.routes.user_datatable_route,
      data: function (d) {
        d.email = $('#users_email').val();
        d.name = $('#users_name').val();
      },
    },
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.user_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'name', name: 'name' },
      { data: 'email', name: 'email' },
      { data: 'roles', name: 'roles' },
      { data: 'teamName', name: 'teamName' },
      {
        data: 'is_active',
        name: 'is_active',
        render: function (data, type, row) {
          return data == 1 ? 'Active' : 'InActive';
        },
      },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('.role-data-table').DataTable({
    ordering: false,
    info: false,
    searching: true,
    bLengthChange: false,
    serverSide: true,
    pageLength: 20,
    ajax: config.routes.role_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.role_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'name', name: 'name' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('.insurancecompany-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.insurancecompany_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.insurancecompany_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'name', name: 'name' },
      { data: 'is_active', name: 'is_active' },
      { data: 'created_by', name: 'created_by' },
      { data: 'updated_by', name: 'updated_by' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('.handler-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.handler_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.handler_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'name', name: 'name' },
      { data: 'is_active', name: 'is_active' },
      { data: 'created_by', name: 'created_by' },
      { data: 'updated_by', name: 'updated_by' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('.status-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.status_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.status_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'name', name: 'name' },
      { data: 'is_active', name: 'is_active' },
      { data: 'created_by', name: 'created_by' },
      { data: 'updated_by', name: 'updated_by' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  var transactionsDatatable = $('.transaction-data-table').DataTable({
    dom: 'Bfrtip',
    buttons: [
      {
        extend: 'csv',
        text: '<i class="fa fa-download" style="color:orange;" id="transapp-export"></i><div id="transapp-export-text" class="required" style="font-weight:bold;"></div>',
        titleAttr: 'Download CSV',
        action: newexportaction,
      },
    ],
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    processing: true,
    stateSave: true,
    scrollX: true,
    paging: true,
    ajax: {
      url: config.routes.transaction_datatable_route,
      data: function (d) {
        d.transapp_start_date = $('#transapp_start_date').val();
        d.transapp_stop_date = $('#transapp_stop_date').val();
        d.transactor = $('#transactor_value').val();
        d.handler = $('#handler_value').val();
        d.insurance_company = $('#insurance_company_value').val();
        d.reason = $('#reason_value').val();
        d.payment_mode = $('#payment_mode_value').val();
        d.transapp_approval_code = $('#transapp_approval_code').val();
        d.transapp_customer_email = $('#customer_email').val();
        d.transapp_customer_name = $('#customer_name').val();
        d.team_id = $('#team').val();
      },
    },
    drawCallback: function () {
      let tableData = this.api().data();
      if (tableData[0]) {
        let totalPremium = tableData[0].premium_total.toFixed(2);
        $('#total_premium_value').html(totalPremium);
      } else {
        $('#total_premium_value').html('0');
      }
    },
    columns: [
      { data: 'approval_code', name: 'approval_code' },
      { data: 'created_at', name: 'created_at' },
      { data: 'insurance', name: 'insurance' },
      { data: 'amount_paid', name: 'amount_paid' },
      { data: 'customer_name', name: 'customer_name' },
      { data: 'risk_details', name: 'risk_details' },
      { data: 'created_by_name', name: 'created_by_name' },
      { data: 'handler_name', name: 'handler_name' },
      { data: 'payment_mode', name: 'payment_mode' },
      { data: 'prev_approval_code', name: 'prev_approval_code' },
    ],
  });

  $('#transapp-export').hide();
  $('#search-transactions').submit(function (e) {
    e.preventDefault();
    var tmLeadsStartDate = $('#transapp_start_date');
    var tmLeadsEndDate = $('#transapp_stop_date');
    var message = $('#message');
    var transappExport = $('#transapp-export');

    var tmLeadsStartDateVal = tmLeadsStartDate.val();
    var tmLeadsEndDateVal = tmLeadsEndDate.val();

    var tmLeadsStartDateVar = new Date(tmLeadsStartDateVal);
    var tmLeadsEndDateVar = new Date(tmLeadsEndDateVal);
    var timeDiff = tmLeadsEndDateVar.getTime() - tmLeadsStartDateVar.getTime();
    var daysDiff = timeDiff / (1000 * 60 * 60 * 24);

    if (tmLeadsStartDateVal == '' || tmLeadsEndDateVal == '') {
      message.html('Please select start & stop dates');
      tmLeadsStartDate.css('border-color', 'red');
      tmLeadsEndDate.css('border-color', 'red');
      transappExport.hide();
      return false;
    }
    if (tmLeadsStartDateVal > tmLeadsEndDateVal) {
      message.html('Start date must be equal or less than stop date');
      tmLeadsStartDate.css('border-color', 'red');
      tmLeadsEndDate.css('border-color', 'red');
      transappExport.hide();
      return false;
    }
    if (daysDiff > 30) {
      message.html(
        'Allowed number of days between start & stop dates are 30 days.',
      );
      tmLeadsStartDate.css('border-color', 'red');
      tmLeadsEndDate.css('border-color', 'red');
      transappExport.hide();
      return false;
    } else {
      transactionsDatatable.draw();
      $('.loader').show();
      message.html('');
      tmLeadsStartDate.css('border-color', '');
      tmLeadsEndDate.css('border-color', '');
      transappExport.show();
      setTimeout(() => {
        $('.loader').hide();
      }, 1000);

      var isTransappAdmin = $('#isTransappAdmin').val();
      if (isTransappAdmin == 1) {
        $("a[title='Download CSV']").show();
      } else {
        $("a[title='Download CSV']").hide();
      }
    }
  });

  $('#transapp-export').click(function () {
    console.log('clicked on export-export');
    $('#transapp-export').hide();
    $('#transapp-export-text').text(
      'Please wait until csv file will be downloaded. More waiting time is depending on number of records.',
    );
    $('#transapp-export-text').show().delay(10000).fadeOut();
  });

  $('#search-users').submit(function (e) {
    e.preventDefault();
    $('.loader').show();
    usersDataTable.draw();
    setTimeout(() => {
      $('.loader').hide();
    }, 1000);
  });

  $('.reason-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.reason_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.reason_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'name', name: 'name' },
      { data: 'is_active', name: 'is_active' },
      { data: 'created_by', name: 'created_by' },
      { data: 'updated_by', name: 'updated_by' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('.paymentmode-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.paymentmode_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.paymentmode_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'name', name: 'name' },
      { data: 'is_active', name: 'is_active' },
      { data: 'created_by', name: 'created_by' },
      { data: 'updated_by', name: 'updated_by' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  var claimsDatatable = $('.claim-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: {
      url: config.routes.claim_datatable_route,
      data: function (d) {
        d.searchtype = $('#search_type').val();
        d.searchfield = $('input[name=searchfield]').val();
        d.claimstatus = $('#claim_status_value').val();
        d.assignedto = $('#assigned_to_value').val();
        d.type_of_insurance = $('#type_of_insurance_value').val();
      },
    },
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.claim_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'ticket_number', name: 'ticket_number' },
      { data: 'policy_number', name: 'policy_number' },
      { data: 'first_name', name: 'first_name' },
      { data: 'last_name', name: 'last_name' },
      { data: 'email_address', name: 'email_address' },
      { data: 'phone_number', name: 'phone_number' },
      { data: 'type_of_insurance_text', name: 'type_of_insurance_text' },
      { data: 'claims_status_text', name: 'claims_status_text' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('#search-claims').submit(function (e) {
    e.preventDefault();
    $('.loader').show();
    claimsDatatable.draw();
    setTimeout(() => {
      $('.loader').hide();
    }, 1000);
  });

  $('.typeofinsurance-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.typeofinsurance_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.typeofinsurance_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'text', name: 'text' },
      { data: 'text_ar', name: 'text_ar' },
      { data: 'sort_order', name: 'sort_order' },
      { data: 'is_active', name: 'is_active' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('.vehicledepreciation-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.vehicledepreciation_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.vehicledepreciation_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'car_make_text', name: 'car_make_text' },
      { data: 'car_model_text', name: 'car_model_text' },
      { data: 'ip_text', name: 'ip_text' },
      { data: 'first_year', name: 'first_year' },
      { data: 'second_year', name: 'first_year' },
      { data: 'third_year', name: 'first_year' },
      { data: 'fourth_year', name: 'first_year' },
      { data: 'fifth_year', name: 'first_year' },
      { data: 'sixth_year', name: 'first_year' },
      { data: 'seventh_year', name: 'first_year' },
      { data: 'eighth_year', name: 'first_year' },
      { data: 'ninth_year', name: 'first_year' },
      { data: 'tenth_year', name: 'first_year' },
    ],
  });

  $('.subtypeofinsurance-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.subtypeofinsurance_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.subtypeofinsurance_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'text', name: 'text' },
      { data: 'text_ar', name: 'text_ar' },
      { data: 'sort_order', name: 'sort_order' },
      { data: 'is_active', name: 'is_active' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('.claimsstatus-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.claimsstatus_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.claimsstatus_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'text', name: 'text' },
      { data: 'text_ar', name: 'text_ar' },
      { data: 'sort_order', name: 'sort_order' },
      { data: 'is_active', name: 'is_active' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('.carrepaircoverage-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.carrepaircoverage_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.carrepaircoverage_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'text', name: 'text' },
      { data: 'text_ar', name: 'text_ar' },
      { data: 'sort_order', name: 'sort_order' },
      { data: 'is_active', name: 'is_active' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('.carrepairtype-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.carrepairtype_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.carrepairtype_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'text', name: 'text' },
      { data: 'text_ar', name: 'text_ar' },
      { data: 'sort_order', name: 'sort_order' },
      { data: 'is_active', name: 'is_active' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('#car_make_id').on('change', function (e) {
    var make_code = $('#car_make_id option:selected').attr('data-id');
    if (!make_code) {
      make_code = $('#car_make_id option:selected').val();
    }
    $.get('/car-model?make_code=' + make_code, function (data) {
      var carmodel = $('#car_model_id').empty();
      carmodel.append(
        '<option data-id="" value="">Please confirm Car Model</option>',
      );
      $.each(data, function (create, carmodelObj) {
        var option = $('<option/>', { id: create, value: carmodelObj });
        carmodel.append(
          '<option data-id="' +
            carmodelObj.code +
            '" value="' +
            carmodelObj.id +
            '">' +
            carmodelObj.text +
            '</option>',
        );
      });
    });
  });

  $('#sub_type_of_insurance').hide();
  $('#car_fields').hide();
  type_of_insurance_fields_visibility();
  $('#type_of_insurances_id').on('change', function (e) {
    type_of_insurance_fields_visibility();
  });

  function type_of_insurance_fields_visibility() {
    var type_of_insurance_text = $(
      '#type_of_insurances_id option:selected',
    ).attr('data-id');
    if (type_of_insurance_text == 'Business') {
      $('#sub_type_of_insurance').show();
    } else {
      $('#sub_type_of_insurance').hide();
    }
    if (type_of_insurance_text == 'Car') {
      $('#car_fields').show();
    } else {
      $('#car_fields').hide();
    }
  }
  $('.payment-link-copy').on('click', function () {
    var planId = $(this).attr('data-planId');
    var quoteUUID = $(this).attr('data-quoteUUId');
    var providerCode = $(this).attr('data-providerCode');
    var websiteURL = $(this).attr('data-websiteURL');
    var paymentLink = `${websiteURL}/car-insurance/quote/${quoteUUID}/payment/?providerCode=${providerCode}&planId=${planId}`;
    navigator.clipboard.writeText(paymentLink);
    var self = this;
    $(this).text('Copied !');
    setTimeout(function () {
      $(self).text('Copy');
    }, 2000);
  });

  $('#btn_copy_doc_upload_link').on('click', function () {
    var doc_upload_url = $(this).data('doc-upload-url');
    navigator.clipboard.writeText(doc_upload_url);
    var obj = this;
    $(this).text('Copied !');
    setTimeout(function () {
      $(obj).text($(obj).data('label'));
    }, 2000);
  });

  var customerDataTable = $('.customer-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: {
      url: config.routes.customer_data_table_route,
      data: function (d) {
        d.searchtype = $('#search_type').val();
        d.searchfield = $('#searchfield').val();
      },
    },
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.customer_data_table_route +
            '/' +
            row.uuid +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'first_name', name: 'first_name' },
      { data: 'email', name: 'email' },
      { data: 'mobile_no', name: 'mobile_no' },
      { data: 'gender', name: 'gender' },
      { data: 'has_alfred_access', name: 'has_alfred_access' },
      { data: 'dob', name: 'dob', orderable: false, searchable: false },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  var vehicleTypeDataTable = $('.base-discount-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: {
      url: config.routes.discount_base_data_table_route,
      data: function (d) {
        d.vehicle_type = $('#vehicle_type').val();
      },
    },
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.discount_base_data_table_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'value_start', name: 'value_start' },
      { data: 'value_end', name: 'value_end' },
      { data: 'vehicle_type_text', name: 'vehicle_type_text' },
      { data: 'comprehensive_discount', name: 'comprehensive_discount' },
      { data: 'agency_discount', name: 'agency_discount' },
      {
        data: 'is_active',
        name: 'is_active',
        orderable: false,
        searchable: false,
      },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  var ageDiscountDataTable = $('.age-discount-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: {
      url: config.routes.age_discount_datatable_route,
    },
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.age_discount_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'age_start', name: 'age_start' },
      { data: 'age_end', name: 'age_end' },
      { data: 'discount', name: 'discount' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('#search-customer').submit(function (e) {
    e.preventDefault();
    $('.loader').show();
    customerDataTable.draw();
    setTimeout(() => {
      $('.loader').hide();
    }, 1000);
  });

  $('#search-vehicleTypes').submit(function (e) {
    e.preventDefault();
    $('.loader').show();
    vehicleTypeDataTable.draw();
    setTimeout(() => {
      $('.loader').hide();
    }, 1000);
  });

  $('#search-vehicleTypes-reset').click(function (e) {
    e.preventDefault();
    $('.loader').show();
    $('#vehicle_type').val($('#vehicle_type option:first').val());
    vehicleTypeDataTable.draw();
    setTimeout(() => {
      $('.loader').hide();
    }, 1000);
  });

  var amlDatatable = $('.aml-data-table').DataTable({
    ordering: false,
    info: true,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    processing: true,
    ajax: {
      url: config.routes.aml_datatable_route,
      error: function (json) {
        if (json.status === 422) {
          var errors = json.responseJSON;
          $.each(errors.errors, function (key, value) {
            $('.' + key + '-error')
              .html(value)
              .css('color', 'red');
          });
        }
      },
      data: function (d) {
        d.searchType = $('#searchType').val();
        d.searchField = $('input[name=searchField]').val();
        d.quoteType = $('#quoteTypeValue').val();
        d.matchFound = $('#matchFound').val();
        d.amlCreatedStartDate = $('#amlCreatedStartDate').val();
        d.amlCreatedEndDate = $('#amlCreatedEndDate').val();
        d.onLoadCheck = $("input[name='onLoadCheck']").val();
      },
    },
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.aml_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'quote_type_text', name: 'quote_type_text' },
      {
        data: 'quote_request_id',
        name: 'quote_request_id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.aml_datatable_route +
            '/' +
            row.quote_type_id +
            '/details/' +
            row.quote_request_id +
            "'>" +
            row.cdb_id +
            '</a>'
          );
        },
      },
      { data: 'input', name: 'input' },
      {
        data: 'screenshot',
        name: 'screenshot',
        render: function (data, type, row, meta) {
          var imgSrc = data;
          if (imgSrc != null) {
            return (
              '<a href="' +
              imgSrc +
              '" target="_blank">' +
              '<img class="img-responsive" src="' +
              imgSrc +
              '" alt="screenshot" height="80px" width="80px"></a>'
            );
          }
        },
      },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('#searchAML').submit(function (e) {
    var amlCreatedStartDate = $('#amlCreatedStartDate').val();
    var amlCreatedEndDate = $('#amlCreatedEndDate').val();
    var searchType = $('#searchType').val();
    var searchField = $('#searchField').val();

    $("input[name='onLoadCheck']").val(0);
    $(
      '.quoteType-error, .searchField-error, .amlCreatedStartDate-error, .amlCreatedEndDate-error',
    )
      .html('')
      .css('color', '');
    $('#amlCreatedStartDate, #amlCreatedEndDate').css({ 'border-color': '' });
    $('#amlCreatedStartDateMsg').html('');

    if (searchType != '' && searchField == '') {
      $('.searchField-error')
        .html('Please select search value')
        .css('color', 'red');
      $('#searchField').css('border-color', 'red');
      return false;
    } else {
      $('.searchField-error').html('').css('color', '');
      $('#searchField').css({ 'border-color': '' });
    }

    if (
      searchType == '' &&
      (amlCreatedStartDate == '' || amlCreatedEndDate == '')
    ) {
      $('#amlCreatedStartDateMsg').html('Please select start & end dates');
      $('#amlCreatedStartDate').css('border-color', 'red');
      $('#amlCreatedEndDate').css('border-color', 'red');
      return false;
    } else if (amlCreatedStartDate != '' && amlCreatedEndDate != '') {
      var amlCreatedStartDateSet = new Date(amlCreatedStartDate);
      var amlCreatedEndDateSet = new Date(amlCreatedEndDate);

      var amlCreatedStartEndTimeDifference =
        amlCreatedEndDateSet.getTime() - amlCreatedStartDateSet.getTime();
      var amlCreatedStartEndDaysDiff =
        amlCreatedStartEndTimeDifference / (1000 * 60 * 60 * 24);

      if (amlCreatedStartEndDaysDiff > 30) {
        $('#amlCreatedStartDateMsg').html(
          'Allowed no. of days between start & end dates are 30 days.',
        );
        $('#amlCreatedStartDate').css('border-color', 'red');
        $('#amlCreatedEndDate').css('border-color', 'red');
        $('#amlCreatedEndDateMsg').html('');
        return false;
      } else if (amlCreatedStartDate > amlCreatedEndDate) {
        $('#amlCreatedStartDateMsg').html(
          'Start date must be equal or less than end date',
        );
        $('#amlCreatedStartDate').css('border-color', 'red');
        $('#amlCreatedEndDate').css('border-color', 'red');
        $('#amlCreatedEndDateMsg').html('');
        return false;
      } else {
        $('#amlCreatedStartDateMsg').html('');
        $('#amlCreatedEndDateMsg').html('');
        $('#amlCreatedStartDate').css('border-color', '');
        $('#amlCreatedEndDate').css('border-color', '');
        e.preventDefault();
        $('.loader').show();
        amlDatatable.draw();
        setTimeout(() => {
          $('.loader').hide();
        }, 1000);
      }
    } else {
      $('#amlCreatedStartDateMsg').html('');
      $('#amlCreatedEndDateMsg').html('');
      $('#amlCreatedStartDate').css('border-color', '');
      $('#amlCreatedEndDate').css('border-color', '');
      e.preventDefault();
      $('.loader').show();
      amlDatatable.draw();
      setTimeout(() => {
        $('.loader').hide();
      }, 1000);
    }
  });

  $('.tminsurancetype-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.tminsurancetype_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.tminsurancetype_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'code', name: 'code' },
      { data: 'text', name: 'text' },
      { data: 'text_ar', name: 'text_ar' },
      { data: 'sort_order', name: 'sort_order' },
      { data: 'is_active', name: 'is_active' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('.tmcallstatus-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.tmcallstatus_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.tmcallstatus_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'code', name: 'code' },
      { data: 'text', name: 'text' },
      { data: 'text_ar', name: 'text_ar' },
      { data: 'sort_order', name: 'sort_order' },
      { data: 'is_active', name: 'is_active' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('.tmleadstatus-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.tmleadstatus_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.tmleadstatus_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'code', name: 'code' },
      { data: 'text', name: 'text' },
      { data: 'text_ar', name: 'text_ar' },
      { data: 'sort_order', name: 'sort_order' },
      { data: 'is_active', name: 'is_active' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  var tmLeadsDatatable = $('.tmlead-data-table').DataTable({
    dom: 'Bfrtip',
    buttons: [
      {
        extend: 'csv',
        text: '<i class="fa fa-download" style="color:orange;" id="tm-leads-export"></i><div id="tm-leads-export-text" class="required" style="font-weight:bold;"></div>',
        titleAttr: 'Download CSV',
        action: newexportaction,
      },
    ],
    ordering: false,
    info: true,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    stateSave: true,
    paging: true,
    processing: true,
    scrollX: true,
    ajax: {
      url: config.routes.tmlead_datatable_route,
      data: function (d) {
        d.searchType = $('#searchType').val();
        d.searchField = $('input[name=searchField]').val();
        d.assigned_to_id = $('#assigned_to_id').val();
        d.tm_insurance_types_id = $('#tm_insurance_types_id').val();
        d.tm_lead_types_id = $('#tm_lead_types_id').val();
        d.tm_lead_statuses_id = $('#tm_lead_statuses_id').val();
        d.tmLeadsStartDate = $('#tmLeadsStartDate').val();
        d.tmLeadsEndDate = $('#tmLeadsEndDate').val();
      },
    },
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row, meta) {
          var isCurrentUserIsAdvisor = $('#isCurrentUserIsAdvisor').val();
          if (isCurrentUserIsAdvisor == 0) {
            return (
              '<input type="checkbox" id="tmLeadID" class="tmleadCheckbox" name="tmLeadID" value="' +
              data +
              '">'
            );
          }
        },
      },
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.tmlead_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.cdb_id +
            '</a>'
          );
        },
      },
      { data: 'customer_name', name: 'customer_name' },
      { data: 'tm_insurance_types_text', name: 'tm_insurance_types_text' },
      { data: 'tm_lead_type', name: 'tm_lead_type' },
      { data: 'tm_lead_status_text', name: 'tm_lead_status_text' },
      { data: 'notes', name: 'notes' },
      { data: 'enquiry_date', name: 'enquiry_date' },
      { data: 'allocation_date', name: 'allocation_date' },
      { data: 'next_followup_date', name: 'next_followup_date' },
      { data: 'handlers_name', name: 'handlers_name' },
      { data: 'tm_created_at', name: 'created_at' },
      { data: 'tm_updated_at', name: 'updated_at' },
    ],
    createdRow: function (row, data, index) {
      if (
        data.tm_lead_status_code == 'NoAnswer' ||
        data.tm_lead_status_code == 'SwitchedOff' ||
        data.tm_lead_status_code == 'PipelineNoInfo' ||
        data.tm_lead_status_code == 'PipelineImmediate' ||
        data.tm_lead_status_code == 'PipelineFuture' ||
        data.tm_lead_status_code == 'DealingWithAnAdvisor'
      ) {
        var d = new Date();
        var currentTimestmap =
          d.getFullYear() +
          '-' +
          ('0' + (d.getMonth() + 1)).slice(-2) +
          '-' +
          ('0' + d.getDate()).slice(-2) +
          ' ' +
          ('0' + d.getHours()).slice(-2) +
          ':' +
          ('0' + d.getMinutes()).slice(-2) +
          ':' +
          ('0' + d.getSeconds()).slice(-2);

        if (currentTimestmap > data.next_followup_date) {
          $('td', row).eq(8).css('color', 'red');
        }
      }
    },
  });
  tmLeadsDatatable.column(4).visible(false);

  // TM Leads: Expost data into csv
  function newexportaction(e, dt, button, config) {
    var self = this;
    var oldStart = dt.settings()[0]._iDisplayStart;
    dt.one('preXhr', function (e, s, data) {
      data.start = 0;
      data.length = 2147483647;
      dt.one('preDraw', function (e, settings) {
        if (button[0].className.indexOf('buttons-csv') >= 0) {
          $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config)
            ? $.fn.dataTable.ext.buttons.csvHtml5.action.call(
                self,
                e,
                dt,
                button,
                config,
              )
            : $.fn.dataTable.ext.buttons.csvFlash.action.call(
                self,
                e,
                dt,
                button,
                config,
              );
        }
        dt.one('preXhr', function (e, s, data) {
          settings._iDisplayStart = oldStart;
          data.start = oldStart;
        });
        setTimeout(dt.ajax.reload, 0);
        return false;
      });
    });
    dt.ajax.reload();
  }

  $('#tm-leads-export').click(function () {
    $('#tm-leads-export').hide();
    $('#tm-leads-export-text').text(
      'Please wait until csv file will be downloaded. More waiting time is depending on number of records.',
    );
    $('#tm-leads-export-text').show().delay(10000).fadeOut();
  });

  $('#tm-leads-upload-csv-button').click(function () {
    $('#tm-leads-upload-csv-button').hide();
    $('#tm-leads-upload-csv-button-text').text(
      'Please wait until csv file will be uploaded. More waiting time is depending on number of records.',
    );
  });

  // TM Leads: Search button trigger
  $('#tm-leads-export').hide();
  $('#search-tm-leads').submit(function (e) {
    e.preventDefault();
    var tmLeadsStartDate = $('#tmLeadsStartDate').val();
    var tmLeadsEndDate = $('#tmLeadsEndDate').val();
    var searchType = $('#searchType').val();

    if (
      searchType == 'created_at' ||
      searchType == 'updated_at' ||
      searchType == 'next_followup_date' ||
      searchType == 'enquiry_date' ||
      searchType == 'allocation_date'
    ) {
      var tmLeadsStartDateVar = new Date(tmLeadsStartDate);
      var tmLeadsEndDateVar = new Date(tmLeadsEndDate);
      var time_difference =
        tmLeadsEndDateVar.getTime() - tmLeadsStartDateVar.getTime();
      var daysDiff = time_difference / (1000 * 60 * 60 * 24);

      if (tmLeadsStartDate == '' || tmLeadsEndDate == '') {
        $('#result').html('Please select start & end dates');
        $('#tmLeadsStartDate').css('border-color', 'red');
        $('#tmLeadsEndDate').css('border-color', 'red');
        $('#tm-leads-export').hide();
        return false;
      } else if (tmLeadsStartDate > tmLeadsEndDate) {
        $('#result').html('Start date must be equal or less than end date');
        $('#tmLeadsStartDate').css('border-color', 'red');
        $('#tmLeadsEndDate').css('border-color', 'red');
        $('#tm-leads-export').hide();
        return false;
      } else if (daysDiff > 30) {
        $('#result').html(
          'Allowed number of days between start and and dates are 30 days.',
        );
        $('#tmLeadsStartDate').css('border-color', 'red');
        $('#tmLeadsEndDate').css('border-color', 'red');
        $('#tm-leads-export').hide();
        return false;
      } else {
        tmLeadsDatatable.draw();
        $('.loader').show();
        $('#result').html('');
        $('#tmLeadsStartDate').css('border-color', '');
        $('#tmLeadsEndDate').css('border-color', '');
        $('#tm-leads-export').show();
        setTimeout(() => {
          $('.loader').hide();
        }, 1000);

        // Hide Download CSV for advisors
        var isCurrentUserIsAdvisor = $('#isCurrentUserIsAdvisor').val();
        if (isCurrentUserIsAdvisor == 0) {
          $("a[title='Download CSV']").show();
        } else {
          $("a[title='Download CSV']").hide();
        }
      }
    } else {
      tmLeadsDatatable.draw();
      $('.loader').show();
      $('#result').html('');
      $('#tmLeadsStartDate').css('border-color', '');
      $('#tmLeadsEndDate').css('border-color', '');
      $('#tm-leads-export').hide();
      setTimeout(() => {
        $('.loader').hide();
      }, 1000);
    }
  });

  $('#healthPlansAll').on('click', function () {
    if ($(this).is(':checked')) {
      $('.health-plans-checkbox').prop('checked', this.checked);
    } else {
      $('.health-plans-checkbox').removeAttr('checked');
    }
  });

  // TM Leads: Select tm leads id and store in hidden field
  $('#checkAllTmLeads').click(function () {
    $('input:checkbox').not(this).prop('checked', this.checked);
    $('#selectTmLeadId').val('');
    var idsArray = $('#selectTmLeadId').val();
    $('input:checkbox').each(function (i, item) {
      idsArray = idsArray + $(item).val() + ',';
    });
    $('#selectTmLeadId').val(idsArray.replace(/^,|,$/g, ''));
    if ($(this).is(':checked')) {
      $('#tm-leads-assign-div').show(300);
    } else {
      $('#tm-leads-assign-div').hide(200);
    }
  });

  $('#assign_team').on('change', function () {
    if ($(this).val() == 'GM') {
      $('#assigned_to_id_new').attr('disabled', true);
    } else {
      $('#assigned_to_id_new').attr('disabled', false);
    }
  });

  // TM Leads: Select tm leads id and store in hidden field
  $('#tmLeadsAssignToUser').click(function () {
    var tmLeadIDs = [];
    if ($('#healthTeamTypeAssignDiv').length > 0) {
      if ($('#assign_team').val() == '') {
        $('#teamErrorSpan').show().fadeOut(5000);
        return false;
      }
    }

    if (!$('#checkAllTmLeads').is(':checked')) {
      $.each($("input[name='tmLeadID']:checked"), function () {
        tmLeadIDs.push($(this).val());
      });
      $('#selectTmLeadId').val(tmLeadIDs);
    }
  });

  $('#assignAfterTeam').click(function () {
    var tmLeadIDs = [];
    if ($('#assign_team').val() == '') {
      $('#teamAssignValidation').show().fadeOut(5000);
      return false;
    }

    if (!$('#checkAllTmLeads').is(':checked')) {
      $.each($("input[name='tmLeadID']:checked"), function () {
        tmLeadIDs.push($(this).val());
      });
      $('#selectTmLeadId').val(tmLeadIDs);
    }
  });

  // TM: Selecting a single record should also enable manual allocation
  $(document).on('change', '#tmLeadID', function () {
    var idsArray = $('#selectTmLeadId').val();
    idsArray = idsArray + ',' + $(this).val() + ',';
    $('#selectTmLeadId').val(idsArray.replace(/^,|,$/g, ''));
    var countSelectedTmLeadIds =
      document.querySelectorAll('#tmLeadID:checked').length;
    if (countSelectedTmLeadIds > 0) {
      $('#tm-leads-assign-div').show(300);
    } else {
      $('#checkAllTmLeads').prop('checked', false);
      $('#tm-leads-assign-div').hide(300);
    }
  });

  // TM Leads: On check main checkbox, display lead assignment panel
  $('#tm-leads-assign-div').hide();

  // TM Leads: OnClick on phone number ignore redirection
  $('#ignore-redirection').click(function () {
    return false;
  });

  // TM Leads: Display Car fields if insurance type Car is selected
  $('#tm_car_fields').hide();
  $('#tm_dob_field').hide();
  tm_type_of_insurance_fields_visibility();
  $('#tm_insurance_types_id').on('change', function (e) {
    tm_type_of_insurance_fields_visibility();
  });

  function tm_type_of_insurance_fields_visibility() {
    var tm_insurance_types_id_code = $(
      '#tm_insurance_types_id option:selected',
    ).attr('data-id');

    if (tm_insurance_types_id_code == 'Car') {
      $('#tm_car_fields').show();
    } else {
      $('#tm_car_fields').hide();
    }

    if (
      tm_insurance_types_id_code == 'Car' ||
      tm_insurance_types_id_code == 'Bike' ||
      tm_insurance_types_id_code == 'Life' ||
      tm_insurance_types_id_code == 'Health' ||
      tm_insurance_types_id_code == 'Critical'
    ) {
      $('#tm_dob_field').show();
    } else {
      $('#tm_dob_field').hide();
    }
  }

  // TM Leads: Display Followup date conditionally
  $('#next_followup_date_field').hide();
  next_followup_date_field_visibility();
  $('#tm_lead_statuses_id').on('change', function (e) {
    next_followup_date_field_visibility();
  });

  function next_followup_date_field_visibility() {
    var tm_lead_status_code = $('#tm_lead_statuses_id option:selected').attr(
      'data-id',
    );

    if (
      tm_lead_status_code == 'NoAnswer' ||
      tm_lead_status_code == 'SwitchedOff' ||
      tm_lead_status_code == 'PipelineNoInfo' ||
      tm_lead_status_code == 'PipelineImmediate' ||
      tm_lead_status_code == 'PipelineFuture' ||
      tm_lead_status_code == 'DealingWithAnAdvisor' ||
      (tm_lead_status_code == 'NewLead' && tmLeadEditFormNextFollowupDate != '')
    ) {
      $('#next_followup_date_field').show();
    } else {
      $('#next_followup_date_field').hide();
    }
  }

  // TM Leads: Display date fields and search value field conditionally
  $('#tmLeads-search-value-filter').show();
  $('#tmLeads-search-start-end-dates-filters').hide();
  $('#searchType').on('change', function (e) {
    tmleads_search_start_end_dates_filters_visiblity();
  });

  function tmleads_search_start_end_dates_filters_visiblity() {
    var searchTypeValue = $('#searchType').val();
    console.log('searchTypeValue: ' + searchTypeValue);
    if (
      searchTypeValue == 'created_at' ||
      searchTypeValue == 'updated_at' ||
      searchTypeValue == 'next_followup_date' ||
      searchTypeValue == 'enquiry_date' ||
      searchTypeValue == 'allocation_date'
    ) {
      $('#tmLeads-search-start-end-dates-filters').show(300);
      $('#tmLeads-search-value-filter').hide(300);
    } else {
      $('#tmLeads-search-start-end-dates-filters').hide(300);
      $('#tmLeads-search-value-filter').show(300);
      $('#tmLeadsStartDate').val('');
      $('#tmLeadsEndDate').val('');
    }
  }

  // TM Leads: OnChange searchType do reset searchField
  $('#search-tm-leads #searchType').on('change', function (e) {
    $('#searchField').val('');
  });

  $('.tmuploadlead-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    stateSave: true,
    paging: true,
    processing: true,
    ajax: config.routes.tmuploadlead_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.tmuploadlead_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'file_name', name: 'file_name' },
      { data: 'good', name: 'good' },
      { data: 'user_name', name: 'user_name' },
      { data: 'created_at', name: 'created_at' },
    ],
  });

  //select/unselect all checkboxes if this selected
  $('#select_all_checkboxes').click(function (e) {
    var isChecked = e.target.checked;

    if (isChecked === true) {
      $('.multicheckbox').each(function (index) {
        $(this).prop('checked', true);
      });
    } else {
      $('.multicheckbox').each(function (index) {
        $(this).prop('checked', false);
      });
    }
  });

  $('.quotePlanModalPopup').on('click', function (e) {
    e.preventDefault();
    $('.quote-plan-modal-body').load(
      $(this).attr('planDetailUrl'),
      function () {
        $('#quotePlanModal').modal({ show: true });
      },
    );
  });
  $('#duplicateLeadModalBtn').on('click', function (e) {
    e.preventDefault();
    $('#duplicateLeadModal').modal({ show: true });
  });
  $('#add-activity-btn').on('click', function () {
    $('#activityModal').modal({ show: true });
  });
  $('#add-edit-travel-members-btn').on('click', function () {
    $('#addTravelMemberModal').modal({ show: true });
  });

  $('#due_date').daterangepicker({
    timePicker: true,
    singleDatePicker: true,
    timePicker24Hour: true,
    locale: {
      format: 'DD-MM-YYYY HH:mm:ss',
    },
  });

  $('#quotePlansGenerateButton').click(function () {
    var quotePlansGenerateUrl = $('#quotePlansGenerateUrl').val();
    navigator.clipboard.writeText(quotePlansGenerateUrl);
    $('#quotePlansGenerateMsg').show(300);
    $('#quotePlansGenerateMsg').hide(2000);
  });

  $('.auditablebtn').click(function () {
    var auditableId = $(this).attr('data-id');
    var auditableType = $(this).attr('data-model');
    $(this).attr('disabled', true);

    $.ajax({
      url: config.routes.load_auditable,
      method: 'POST',
      data: { auditableId, auditableType, _token: config._token },
      success: function (data) {
        $('#auditable').html(data);
        $('.auditablebtn').hide();
      },
    });
  });

  $('.apilogsbtn').click(function () {
    var auditableId = $(this).attr('data-id');
    var auditableType = $(this).attr('data-model');
    $(this).attr('disabled', true);

    $.ajax({
      url: config.routes.load_apilogs,
      method: 'POST',
      data: { auditableId, auditableType, _token: config._token },
      success: function (data) {
        $('#apilogsdiv').html(data);
        $('.apilogsbtn').hide();
      },
    });
  });

  // dateRangePickerChange("", "");
  // $(".x_panel transparent > .applyBtn, .ranges li").click(function () {
  //     setTimeout(() => {
  //         var date = $("#reportrange span").html();
  //         var dateAsArray = date.split("-");
  //         var startDate = moment(dateAsArray[0]).format("YYYY-MM-DD");
  //         var endDate = moment(dateAsArray[1]).format("YYYY-MM-DD");
  //         dateRangePickerChange(startDate, endDate);
  //     }, 1000);
  // });
  $('#return_to_view').click(function (e) {
    e.preventDefault();
    var input = '<input name="return_to_view" type="hidden" value="1"/>';
    $('#redirect_to_view_div').html(input);
    setTimeout(function () {
      $('#demo-form2').submit();
    }, 500);
  });

  $('.manager-renewals-leads-data-table').DataTable({
    ordering: false,
    info: true,
    searching: false,
    bLengthChange: false,
    processing: true,
    stateSave: true,
    paging: true,
    ajax: config.routes.renewals_leads_datatable_route,
    columns: [
      { data: 'id', name: 'id' },
      { data: 'renewal_import_type', name: 'renewal_import_type' },
      { data: 'renewal_import_code', name: 'renewal_import_code' },
      { data: 'file_name', name: 'file_name' },
      { data: 'total_records', name: 'total_records' },
      {
        data: 'good',
        name: 'good',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.renewal_uploaded_leads +
            '/' +
            row.id +
            "/validation-passed'>" +
            row.good +
            '</a>'
          );
        },
      },
      {
        data: 'cannot_upload',
        name: 'cannot_upload',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.renewal_uploaded_leads +
            '/' +
            row.id +
            "/validation-failed'>" +
            row.cannot_upload +
            '</a>'
          );
        },
      },
      { data: 'status', name: 'status' },
      { data: 'uploaded_by', name: 'uploaded_by' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('.renewals-leads-data-table').DataTable({
    ordering: false,
    info: true,
    searching: false,
    bLengthChange: false,
    processing: true,
    stateSave: true,
    paging: true,
    ajax: config.routes.renewals_leads_datatable_route,
    columns: [
      { data: 'id', name: 'id' },
      { data: 'renewal_import_type', name: 'renewal_import_type' },
      { data: 'renewal_import_code', name: 'renewal_import_code' },
      { data: 'file_name', name: 'file_name' },
      { data: 'total_records', name: 'total_records' },
      {
        data: 'good',
        name: 'good',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.renewal_uploaded_leads +
            '/' +
            row.id +
            "/validation-passed'>" +
            row.good +
            '</a>'
          );
        },
      },
      {
        data: 'cannot_upload',
        name: 'cannot_upload',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.renewal_uploaded_leads +
            '/' +
            row.id +
            "/validation-failed'>" +
            row.cannot_upload +
            '</a>'
          );
        },
      },
      { data: 'status', name: 'status' },
      { data: 'uploaded_by', name: 'uploaded_by' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('.validation-failed-data-table').DataTable({
    ordering: false,
    info: true,
    searching: false,
    bLengthChange: false,
    processing: true,
    stateSave: true,
    paging: true,
    ajax: config.routes.renewal_uploaded_leads,
    columns: [{ data: 'batch', name: 'batch' }],
  });

  $('#car_make_value').on('change', function (e) {
    var make_code = $('#car_make_value option:selected').attr('data-id');
    $.get('/valuation/car-models?make_code=' + make_code, function (data) {
      var carmodel = $('#car_model_value').empty();
      carmodel.append('<option value="">Select</option>');
      $.each(data, function (create, carmodelObj) {
        var option = $('<option/>', { id: create, value: carmodelObj });
        carmodel.append(
          '<option value="' +
            carmodelObj.id +
            '">' +
            carmodelObj.text +
            '</option>',
        );
      });
    });
  });
  $('#car_model_value').on('change', function (e) {
    var modelId = $('#car_model_value option:selected').val();
    $.get('/valuation/car-model-detail?modelId=' + modelId, function (data) {
      var cartrim = $('#car_trim_value').empty();
      cartrim.append('<option value="">Select</option>');
      if (data.length == 0) {
        cartrim.append('<option value="">No Trim Available</option>');
        $('#car_trim_value option:eq(1)').prop('selected', true);
      }
      $.each(data, function (create, cartrimObj) {
        var option = $('<option/>', { id: create, value: cartrimObj });
        cartrim.append(
          '<option value="' +
            cartrimObj.id +
            '">' +
            cartrimObj.text +
            '</option>',
        );
      });
    });
  });

  $(document).on('click', '.delete', function () {
    var route = $(this).attr('date-route');
    $('#delete-form').attr('action', route);
    $('#exampleModal').modal('show');
  });

  // function dateRangePickerChange(startDate, endDate) {
  //     $(".loader").show();
  //     $.ajax({
  //         url: config.routes.load_dashboard_stats,
  //         method: "POST",
  //         data: { startDate, endDate, _token: config._token },
  //         success: function (data) {
  //             console.log("request ", data);
  //             $(".customer-count").html(data.totalCustomers);
  //             $(".carquote-count").html(data.totalCarQuotes);
  //             $(".ecomleads-count").html(data.totalEcommerceLeads);
  //             $(".fakeleads-count").html(data.totalFakeLeads);
  //             $(".duration").html(startDate + " - " + endDate);
  //             $(".loader").hide();
  //         },
  //     });
  // }
  var corpLineDataTable = $('.corpline-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: {
      url: config.routes.amtDataTable,
      data: function (d) {
        d.leadType = $('#leadStatus').val();
        d.cdbID = $('#cdbID').val();
      },
    },
    columns: [
      {
        data: 'code',
        name: 'code',
        render: function (data, type, row) {
          var href = '/quotes/business/' + row.uuid;
          return "<a href='" + href + "'>" + row.code + '</a>';
        },
      },
      { data: 'first_name', name: 'first_name' },
      { data: 'last_name', name: 'last_name' },
      { data: 'leadStatus', name: 'leadStatus' },
      { data: 'leadType', name: 'leadType' },
      { data: 'created_at', name: 'created_at' },
      { data: 'updated_at', name: 'updated_at' },
    ],
  });

  $('#manualAssignBtn').on('click', function (e) {
    e.preventDefault();
    if ($('#assigned_to_id_new').val() == '') {
      $('#userAssignValidation').show().fadeOut(5000);
    } else {
      $.ajax({
        url: '/leadassignment/manualLeadAssign',
        type: 'PUT',
        data: {
          selectTmLeadId: $('#entityId').val(),
          assigned_to_id_new: $('#assigned_to_id_new').val(),
          _token: config._token,
        },
        success: function (response) {
          $('#teamassignmentSuccess')
            .html('Team Assigned Successfully')
            .show()
            .fadeOut(5000);
          setTimeout(() => {
            window.location.reload(true);
          }, 2000);
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.log(jqXHR, textStatus, errorThrown);
        },
      });
    }
  });
  $('#group_medical_type_id').on('change', function () {
    var txt = $(this).find('option:selected').data('id');
    if (txt) {
      txt = txt.replace(/(\r\n|\n|\r)/gm, '');
      $('#tooltipGm').show();
      $('#tooltipGm').attr('title', txt);
    } else {
      $('#tooltipGm').hide();
    }
  });
  $('#lob_team').select2({
    placeholder: 'Select LOB For Duplication',
    allowClear: true,
    width: '100%',
  });

  $('.vehiclevalue-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    ajax: config.routes.vehiclevalue_datatable_route,
    columns: [
      {
        data: 'id',
        name: 'id',
        render: function (data, type, row) {
          return (
            "<a href='" +
            config.routes.vehiclevalue_datatable_route +
            '/' +
            row.id +
            "'>" +
            row.id +
            '</a>'
          );
        },
      },
      { data: 'car_make_text', name: 'car_make_text' },
      { data: 'car_model_text', name: 'car_model_text' },
      { data: 'car_trim_text', name: 'car_trim_text' },
      { data: 'ip_text', name: 'ip_text' },
      { data: 'current_value', name: 'current_value' },
    ],
  });
  // $('.vehiclerange-data-table').DataTable({
  //     ordering: false,
  //     info: false,
  //     searching: false,
  //     bLengthChange: false,
  //     serverSide: true,
  //     ajax: config.routes.vehiclerange_datatable_route,
  //     columns: [{
  //         data: 'id',
  //         name: 'id',
  //         render: function (data, type, row) {
  //             return "<a href='" + config.routes.vehiclerange_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
  //         }
  //     },
  //     { data: 'car_make_text', name: 'car_make_text' },
  //     { data: 'car_model_text', name: 'car_model_text' },
  //     { data: 'ip_text', name: 'ip_text' },
  //     { data: 'lower_limit', name: 'lower_limit' },
  //     { data: 'upper_limit', name: 'upper_limit' },
  //     ]
  // });

  $('#loadHistoryDataBtn').on('click', function (e) {
    e.preventDefault();
    var modelType = $('input[name=modelType]').val().toLowerCase();
    var leadId = $('input[name=leadId]').val();

    $.ajax({
      url:
        '/quotes/getLeadHistory?modelType=' + modelType + '&recordId=' + leadId,
      type: 'GET',
      success: function (response) {
        var html = '';
        if (response.length > 0) {
          for (let i = 0; i < response.length; i++) {
            const element = response[i];
            element.ModifiedAt =
              element.ModifiedAt == null ? '' : element.ModifiedAt;
            element.ModifiedBy =
              element.ModifiedBy == null ? '' : element.ModifiedBy;
            element.NewStatus =
              element.NewStatus == null ? '' : element.NewStatus;
            element.NewAdvisor =
              element.NewAdvisor == null ? '' : element.NewAdvisor;
            element.NewNotes = element.NewNotes == null ? '' : element.NewNotes;

            html =
              html +
              '<tr><td>' +
              element.ModifiedAt +
              '</td><td>' +
              element.ModifiedBy +
              '</td><td>' +
              element.NewNotes +
              '</td><td>' +
              element.NewStatus +
              '</td></tr>';
          }
        } else {
          html =
            '<tr><td colspan="5" style="text-align: center">No data available</td></tr>';
        }
        $('#leadhistorydatatable tbody').html(html);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log(jqXHR, textStatus, errorThrown);
      },
    });
  });

  $('#loadQuoteStatusLog').on('click', function (e) {
    e.preventDefault();
    var modelType = $('input[name=modelType]').val().toLowerCase();
    var leadId = $('input[name=leadId]').val();
    let quoteTypeId = $(this).attr('data-quote-type-id');

    $.ajax({
      url:
        '/quotes/lead-history?modelType=' +
        modelType +
        '&recordId=' +
        leadId +
        '&quoteTypeId=' +
        quoteTypeId,
      type: 'GET',
      success: function (response) {
        var html = '';
        if (response.length > 0) {
          for (let i = 0; i < response.length; i++) {
            const leadHistory = response[i];
            html =
              html +
              '<tr><td>' +
              (leadHistory?.created_at == null ? '' : leadHistory.created_at) +
              '</td><td>' +
              (leadHistory?.created_by?.email
                ? leadHistory.created_by.email
                : '') +
              '</td><td>' +
              (leadHistory?.previous_quote_status?.text
                ? leadHistory.previous_quote_status.text
                : '') +
              '</td>' +
              '<td>' +
              (leadHistory?.current_quote_status?.text
                ? leadHistory.current_quote_status.text
                : '') +
              '</td><td>' +
              (leadHistory?.notes ? leadHistory.notes : '') +
              '</td></tr>';
          }
        } else {
          html =
            '<tr><td colspan="5" style="text-align: center">No data available</td></tr>';
        }
        $('#quoteStatusLogsTable tbody').html(html);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log(jqXHR, textStatus, errorThrown);
      },
    });
  });

  disabledDoneActivities();

  $('.activityChk1').on('change', function (e) {
    $.ajax({
      url: '/activities/updateStatus',
      method: 'POST',
      data: {
        activity_id: $(this).val(),
        _token: $('input[name=_token]').val(),
      },
      success: function (data) {
        disabledDoneActivities();
      },
    });
  });
  $('.renewals-batches-data-table').DataTable({
    ordering: false,
    info: false,
    searching: false,
    bLengthChange: false,
    serverSide: true,
    stateSave: true,
    paging: true,
    processing: true,
    ajax: config.routes.renewals_batches_datatable_route,
    columns: [
      { data: 'renewal_batch', name: 'renewal_batch' },
      {
        data: 'renewal_batch',
        name: 'manage_plans',
        render: function (data, type, row) {
          return (
            "<a class='btn btn-info btn-sm' href='" +
            config.routes.renewals_batches_datatable_route +
            '/' +
            row.renewal_batch +
            "/plans-processes'>" +
            'Fetch Plans' +
            '</a>' +
            "<a class='btn btn-warning btn-sm' href='" +
            config.routes.renewals_batches_datatable_route +
            '/' +
            row.renewal_batch +
            "'>" +
            'Send Emails</a>'
          );
        },
      },
    ],
  });

  $('.generateCCLink').click(function () {
    $('.loader').show();
    $.ajax({
      url: '/generate-payment-link',
      method: 'POST',
      data: {
        _token: $('input[name=_token]').val(),
        modelType: $(this).attr('data-modelType'),
        quoteId: $(this).attr('data-quoteId'),
        paymentCode: $(this).attr('data-paymentCode'),
      },
      success: function (data) {
        if (data.success) {
          navigator.clipboard.writeText(data.payment_link);
          $('.loader').hide();
          $('#generateCCLinkMsg').show().delay(1000).fadeOut();
        } else {
          $('.loader').hide();
          alert(data);
        }
      },
    });
  });

  $('.btn-change-insurer').on('click', function () {
    if (confirm('Are you sure to change insurer')) {
      let btn = $(this);
      $(btn).btnLoader();

      $.ajax({
        url: '/quotes/car/change-insurer',
        method: 'POST',
        data: {
          uuid: $(this).attr('data-uuid'),
          plan_id: $(this).attr('data-planId'),
          provider_code: $(this).attr('data-providerCode'),
          _token: $('input[name=_token]').val(),
        },
        success: function (data) {
          $(btn).btnResetLoader();
          setTimeout(function () {
            alert(data.message);
            window.location.reload();
          }, 0);
        },
        error: function (response) {
          $(btn).btnResetLoader();
          let errorMsg = '';
          $.each(response.responseJSON.errors, function (key, error) {
            errorMsg += error + '\n';
          });
          alert(errorMsg);
        },
      });
    } else {
      return false;
    }
  });

  $.fn.btnLoader = function () {
    let loading_text = "<i class='fa fa-spinner fa-spin'></i> " + this.html();
    this.data('original-text', this.html());
    this.html(loading_text).attr('disabled', 'disabled');
    return this;
  };

  $.fn.btnResetLoader = function () {
    let original_text = this.data('original-text');
    this.html(original_text).removeAttr('disabled');
    return this;
  };

  $('.payment-link-copy').on('click', function () {
    var planId = $(this).attr('data-planId');
    var quoteUUID = $(this).attr('data-quoteUUId');
    var providerCode = $(this).attr('data-providerCode');
    var websiteURL = $(this).attr('data-websiteURL');
    var paymentLink = `${websiteURL}/car-insurance/quote/${quoteUUID}/payment/?providerCode=${providerCode}&planId=${planId}`;
    navigator.clipboard.writeText(paymentLink);
    var self = this;
    $(this).text('Copied !');
    setTimeout(function () {
      $(self).text('Copy');
    }, 2000);
  });
  $('.enable-bnpl').on('click', function () {
    var planId = $(this).attr('data-planId');
    var quoteUUID = $(this).attr('data-quoteUUId');

    $.ajax({
      url: '/update-car-plan-details',
      method: 'POST',
      data: {
        plan_id: planId,
        quote_uuid: quoteUUID,
        _token: config._token,
      },
      success: function (data) {
        window.location.reload();
      },
    });
  });

  $('#btn_download_plan_pdf').on('click', function () {
    if ($("input[name='toggle_plans_checkbox']:checked").length < 3) {
      alert('Please select at least three (3) plans to download pdf.');
      return false;
    }

    if ($("input[name='toggle_plans_checkbox']:checked").length > 5) {
      alert('A maximum of five (5) plans are allowed to be selected.');
      return false;
    }

    var plan_ids = [];

    $.each($("input[name='toggle_plans_checkbox']:checked"), function () {
      plan_ids.push($(this).val());
    });

    $('#plan_ids').val(plan_ids);
    $('#form_plans_pdf').submit();
  });

  $('select[name="segment_volume[]"]').change(function () {
    var selectedValues = $(this).val();
    $('select[name="segment_value[]"] option').prop('disabled', false);
    $('select[name="segment_value[]"] option')
      .filter(function () {
        return selectedValues.includes($(this).val());
      })
      .prop('disabled', true)
      .addClass('unavailable-option');
  });

  $('select[name="segment_value[]"]').change(function () {
    var selectedValues = $(this).val();
    $('select[name="segment_volume[]"] option').prop('disabled', false);
    $('select[name="segment_volume[]"] option')
      .filter(function () {
        return selectedValues.includes($(this).val());
      })
      .prop('disabled', true)
      .addClass('unavailable-option');
  });
});

$('#btn_download_plan_pdf_health').on('click', function () {
  if ($("input[name='health_plans_checkbox']:checked").length < 3) {
    alert('Please select at least three (3) plans.');
    return false;
  }

  if ($("input[name='health_plans_checkbox']:checked").length > 5) {
    alert('A maximum of five (5) plans are allowed to be selected.');
    return false;
  }

  var plan_ids = [];

  $.each($("input[name='health_plans_checkbox']:checked"), function () {
    plan_ids.push($(this).val());
  });

  $('#plan_ids').val(plan_ids);
  $('#form_plans_pdf').submit();
});

$('#renewals-upload-button').click(function () {
  $('#renewals-upload-button').hide();
  $('#renewals-upload-button-text').text(
    'Please wait until file will be uploaded. More waiting time is depending on number of records.',
  );
});

function activityEdit1(el) {
  var id = $(el).attr('data-record-id');
  var type = $(el).attr('data-type');
  var quote_uuid = $(el).attr('data-quote-uuid');
  $.ajax({
    url: '/activities/getEditView',
    method: 'POST',
    data: {
      activity_id: id,
      quoteType: type,
      quote_uuid: quote_uuid,
      _token: $('input[name=_token]').val(),
    },
    success: function (data) {
      $('#activityEditModalContent').html(data);
      $('#activityEditModalContent > form').append(
        '<input type="hidden" name="fromLeadView" value="1">',
      );
      $('#activityEditModal').modal('show');
    },
  });
}

function isActivityFormValid() {
  var isValid = true;
  if ($('#title').val() == '') {
    $('#title').next('span').html('Title is required').delay(5000).hide(0);
    isValid = false;
  }
  if ($('#description').val() == '') {
    $('#description')
      .next('span')
      .html('Description is required')
      .delay(5000)
      .hide(0);
    isValid = false;
  }
  if ($('#due_date').val() == '') {
    $('#due_date')
      .next('span')
      .html('Due Date is required')
      .delay(5000)
      .hide(0);
    isValid = false;
  }
  if ($('#assignee_id').val() == '') {
    $('#assignee_id')
      .next('span')
      .html('Assignee is required')
      .delay(5000)
      .hide(0);
    isValid = false;
  }
  return isValid;
}

function submitUpdateActivity(el) {
  var uuid = $(el).attr('data-record-id');
  if (isActivityFormValid()) {
    var id = $(el).attr('data-record-id');
    var type = $(el).attr('data-type');
    var quote_uuid = $(el).attr('data-quote-uuid');
    $.ajax({
      url: '/activities/' + uuid + '/update',
      method: 'POST',
      data: {
        title: $('#title').val(),
        description: $('#description').val(),
        due_date: $('#due_date').val(),
        assignee_id: $('#assignee_id').val(),
        _token: $('input[name=_token]').val(),
      },
      success: function (data) {
        $('#activityEditModal').modal('hide');
        $('#sucess-div')
          .text('Activity updated successfully')
          .show()
          .delay(5000)
          .hide(0);
      },
    });
  } else return false;
}

function deleteActivity1(el) {
  if (confirm('Are you sure you want to delete this activity?')) {
    var id = $(el).attr('data-record-id');
    var quote_uuid = $(el).attr('data-quote-uuid');
    var type = $(el).attr('data-type');
    $.ajax({
      url: '/activities/' + id + '/delete',
      method: 'POST',
      data: {
        isLeadView: 1,
        quote_uuid: quote_uuid,
        _token: $('input[name=_token]').val(),
        quoteType: type,
      },
      success: function (data) {
        window.location.reload();
      },
    });
  } else {
    return false;
  }
}

function disabledDoneActivities() {
  $('.activityChk1').each(function (index, el) {
    if ($(el).is(':checked') == true) {
      $(el).attr('disabled', true);
      $(el).closest('td').siblings().find('button').attr('disabled', true);
    }
  });
}

$('#send-note-for-customer-btn').on('click', function () {
  $('#notesForCustomerModal').modal({ show: true });
});

$(
  '#quote_policy_issuance_date, #quote_policy_start_date, #quote_policy_expiry_date',
).datepicker({
  changeMonth: true,
  changeYear: true,
  dateFormat: 'dd-mm-yy',
});

// allow only numbers and decimal (length: 8), ref html: onkeypress="return isNumberKey(event,this)"
function isNumberKey(evt, obj) {
  var charCode = evt.which ? evt.which : event.keyCode;
  var value = obj.value;
  var dotcontains = value.indexOf('.') != -1;
  if (dotcontains) if (charCode == 46) return false;
  if (obj.value.length > 8) return false;
  if (charCode == 46) return true;
  if (charCode > 31 && (charCode < 48 || charCode > 57)) return false;
  return true;
}

function editTravelMemberDetail(member) {
  $.ajax({
    url: '/travelers/' + member + '/edit',
    method: 'GET',
    success: function (data) {
      $('#member_model_content_form').html(data);
      $('#addTravelMemberModal').modal({ show: true });
    },
  });
}

function deleteTravelMemberDetail(member) {
  if (confirm('Are you sure you want to delete this member?')) {
    $.ajax({
      url: '/travelers/' + member,
      method: 'delete',
      data: {
        _token: $('input[name=_token]').val(),
      },
      success: function (data) {
        location.reload();
      },
    });
  } else {
    return false;
  }
}

$('#dataTableCarQuotePlans').DataTable({
  paging: false,
  ordering: false,
  info: false,
  searching: false,
  bLengthChange: false,
  scrollX: true,
});

function deleteQuoteDocument(el) {
  if (confirm('Are you sure to delete this document?')) {
    var quoteId = $(el).attr('data-quote-id');
    var documentName = $(el).attr('data-document-name');
    console.log(documentName);
    $('.loader').show();
    $.ajax({
      url: '/documents/delete',
      method: 'POST',
      data: {
        docName: documentName,
        quoteId: quoteId,
        _token: $('input[name=_token]').val(),
      },
      success: function (data) {
        $('.loader').hide();
        $('#document-delete-success').show();
        setTimeout(function () {
          alert('Document has been deleted.');
          window.location.reload();
        }, 0);
      },
    });
  } else {
    return false;
  }
}
$('#toggle-plans-div').hide();
$('#flowcheckall').click(function (e) {
  if ($(this).hasClass('checkedAll')) {
    $('#toggle-plans-div').hide(200);
    $('.car_plans_checkbox').prop('checked', false);
    $(this).removeClass('checkedAll');
  } else {
    $('.car_plans_checkbox').prop('checked', true);
    $(this).addClass('checkedAll');
    $('#toggle-plans-div').show(300);
  }
});
$('.car_plans_checkbox').click(function (e) {
  if ($(this).is(':checked')) {
    $('#toggle-plans-div').show(300);
  } else {
    $('#toggle-plans-div').hide(200);
  }
});

$('#togglePlans').on('click', function () {
  if ($('#toggle').val() != '') {
    var planIds = [];
    $.each($("input[name='toggle_plans_checkbox']:checked"), function () {
      planIds.push($(this).val());
    });
    $('#planIds').val(planIds);
    $('#togglePlanForm').submit();
  }
});

// Car Quote: Change the type than format the date
$('#dob').prop('type', 'text');
$('#dob_div #dob').datepicker({
  // TM Leads
  changeMonth: true,
  changeYear: true,
  dateFormat: 'dd-mm-yy',
  yearRange: '-80:+00',
});

$('#policy_start_date').prop('type', 'text');
$('#policy_start_date').datepicker({
  changeMonth: true,
  changeYear: true,
  dateFormat: 'dd/mm/yy',
  yearRange: '-80:+00',
  minDate: new Date(),
});

function sendQuoteDocumentsToCustomer(el) {
  if (confirm('Are you sure you want to send documents to customer?')) {
    var quoteType = $(el).attr('data-quote-type');
    var quoteUuId = $(el).attr('data-quote-uuid');
    $('.loader').show();
    $.ajax({
      url: '/quotes/' + quoteType + '/' + quoteUuId + '/send-policy-documents',
      method: 'POST',
      data: {
        _token: $('input[name=_token]').val(),
      },
      success: function (data) {
        $('.loader').hide();
        if (data.error) {
          $('#document-delete-success').text(data.error);
          $('#document-delete-success').show();
        } else {
          $('#email-send-success').show();
          setTimeout(function () {
            window.location.reload();
          }, 10000);
        }
      },
    });
  } else {
    return false;
  }
}

// Button: Send One click buy email - Start
$('#send-one-click-buy-email-btn').on('click', function () {
  if (confirm('Are you sure send email to customer?')) {
    var quote_type = $(this).attr('data-quote-type');
    var quote_uuid = $(this).attr('data-quote-uuid');
    $('.loader').show();
    $.ajax({
      url:
        '/quotes/' +
        quote_type +
        '/' +
        quote_uuid +
        '/send-email-one-click-buy',
      method: 'POST',
      data: {
        quote_type_id: $(this).attr('data-quote-type-id'),
        quote_id: $(this).attr('data-quote-id'),
        quote_uuid: quote_uuid,
        quote_cdb_id: $(this).attr('data-quote-cdb-id'),
        quote_previous_expiry_date: $(this).attr(
          'data-quote-previous-expiry-date',
        ),
        quote_currently_insured_with: $(this).attr(
          'data-quote-currently-insured-with',
        ),
        quote_car_make: $(this).attr('data-quote-car-make'),
        quote_car_model: $(this).attr('data-quote-car-model'),
        quote_car_year_of_manufacture: $(this).attr(
          'data-quote-car-year-of-manufacture',
        ),
        quote_previous_policy_number: $(this).attr(
          'data-quote-previous-policy-number',
        ),
        customer_name: $(this).attr('data-quote-customer-name'),
        customer_email: $(this).attr('data-quote-customer-email'),
        advisor_name: $(this).attr('data-quote-advisor-name'),
        advisor_email: $(this).attr('data-quote-advisor-email'),
        advisor_mobile_no: $(this).attr('data-quote-advisor-mobile-no'),
        advisor_landline_no: $(this).attr('data-quote-advisor-landline-no'),
        _token: $('input[name=_token]').val(),
      },
      success: function (data) {
        $('.loader').hide();
        alert(data.success);
      },
      error: function (jqXHR) {
        $('.loader').hide();
        alert(jqXHR.responseJSON.error);
      },
    });
  } else {
    return false;
  }
});
// Button: Send One click buy email - End

var teamsDataTable = $('.teams-data-table').DataTable({
  ordering: false,
  info: false,
  searching: false,
  bLengthChange: false,
  serverSide: true,
  ajax: {
    url: config.routes.teams_datatable_route,
    data: function (d) {
      d.name = $('#name').val();
    },
  },
  columns: [
    {
      data: 'id',
      name: 'id',
      render: function (data, type, row) {
        return (
          "<a href='" +
          config.routes.teams_datatable_route +
          '/' +
          row.id +
          "'>" +
          row.id +
          '</a>'
        );
      },
    },
    { data: 'name', name: 'name' },
    { data: 'type', name: 'type' },
    { data: 'parent.name', name: 'parent.name' },
    { data: 'created_at', name: 'created_at' },
    { data: 'updated_at', name: 'updated_at' },
    { data: 'is_active', name: 'is_active' },
  ],
});

var renewalBatchesDataTable = $('.renewal-batches-data-table').DataTable({
  ordering: false,
  info: false,
  searching: false,
  bLengthChange: false,
  serverSide: true,
  columns: [
    {
      data: 'id',
      name: 'id',
      // render: function (data, type, row) {
      //   return (
      //     "<a href='" +
      //     config.routes.teams_datatable_route +
      //     '/' +
      //     row.id +
      //     "'>" +
      //     row.id +
      //     '</a>'
      //   );
      // },
    },
    { data: 'name', name: 'name' },
    { data: 'start_date', name: 'start_date' },
    { data: 'end_date', name: 'end_date' },
    {
      data: 'action',
      name: 'action',
      orderable: false,
      searchable: false,
      render: function (data, type, row) {
        return (
          "<a class='btn btn-info' href='" +
          config.routes.renewal_batched_datatable_route +
          '/' +
          row.id +
          "/edit'>Edit</a>"
        );
      },
    },
  ],
});

/**
 * Commercial keywords datatable
 */
var commercialKeywordsDataTable = $(
  '.commercial-keywords-data-table',
).DataTable({
  ordering: false,
  info: false,
  searching: false,
  bLengthChange: false,
  serverSide: true,
  ajax: {
    url: config.routes.commercial_keywords_datatable_route,
    data: function (d) {
      d.name = $('#name').val();
    },
  },
  columns: [
    {
      data: 'id',
      name: 'id',
      render: function (data, type, row) {
        return (
          "<a href='" +
          config.routes.commercial_keywords_datatable_route +
          '/view/' +
          row.id +
          "'>" +
          row.id +
          '</a>'
        );
      },
    },
    { data: 'key', name: 'key' },
    { data: 'name', name: 'name' },
    { data: 'created_at', name: 'created_at' },
    { data: 'updated_at', name: 'updated_at' },
  ],
});

/**
 * Commercial vehicles datatable
 */
var commercialVehiclesDataTable = $(
  '.commercial-vehicles-data-table',
).DataTable({
  ordering: false,
  info: false,
  searching: false,
  bLengthChange: false,
  serverSide: true,
  ajax: {
    url: config.routes.commercial_vehicles_datatable_route,
    data: function (d) {
      d.text = $('#text').val();
    },
  },
  columns: [
    {
      data: 'id',
      name: 'id',
      render: function (data, type, row) {
        return (
          "<a href='" +
          config.routes.commercial_vehicles_datatable_route +
          '/view/' +
          row.id +
          "'>" +
          row.id +
          '</a>'
        );
      },
    },
    { data: 'text', name: 'text' },
    { data: 'code', name: 'code' },
    { data: 'car_models', name: 'car_models', title: 'Commercial Car Models' },
  ],
});

$('#search-teams').submit(function (e) {
  e.preventDefault();
  $('.loader').show();
  teamsDataTable.draw();
  setTimeout(() => {
    $('.loader').hide();
  }, 1000);
});

$('#search-keywords').submit(function (e) {
  e.preventDefault();
  $('.loader').show();
  commercialKeywordsDataTable.draw();
  setTimeout(() => {
    $('.loader').hide();
  }, 1000);
});

$('#search-car-make').submit(function (e) {
  e.preventDefault();
  $('.loader').show();
  commercialVehiclesDataTable.draw();
  setTimeout(() => {
    $('.loader').hide();
  }, 1000);
});

// Listen for change events on select1
$('#rule_type').change(function () {
  var selectedValue = $(this).val(); // Get the selected value

  if (selectedValue == 1) {
    $('#lead_source_id_div').show();
  } else {
    $('#lead_source_id_div').hide();
    $('#lead_source_id').val('');
  }
});

$('#rule_car_make_id').on('change', function (e) {
  console.log('changed');
  var make_code = $('#rule_car_make_id option:selected').attr('data-id');
  if (!make_code) {
    console.log('Car make code not found');
    return;
  }
  $.get('/commercial-car-model-by-id?make_code=' + make_code, function (data) {
    var carmodel = $('#rule_car_model_id').empty();
    carmodel.append(
      '<option data-id="" value="">Please select rule car model</option>',
    );
    if (data.length > 0) {
      $.each(data, function (create, carmodelObj) {
        var option = $('<option/>', { id: create, value: carmodelObj });
        carmodel.append(
          '<option data-id="' +
            carmodelObj.code +
            '" value="' +
            carmodelObj.id +
            '" selected>' +
            carmodelObj.text +
            '</option>',
        );
      });
    } else {
      carmodel.append(
        '<option disabled>No commercial vehicle record found for this car make</option>',
      );
    }
  });
});

// Add new additional contact modal
$('#additional-contact-add-btn').on('click', function () {
  $('#customer-additional-contact-add-modal').modal({ show: true });
});

// Make additional email primary
$('.additional-email-make-primary-btn').on('click', function () {
  var _this = $(this);
  if (confirm('Are you sure to make this primary email address?')) {
    $('.loader').show();
    $.ajax({
      url: '/customer-primary-email-check',
      method: 'POST',
      data: {
        key: $(this).attr('data-key'),
        value: $(this).attr('data-value'),
        _token: $('input[name=_token]').val(),
      },
      success: function (data) {
        if (data.response === true) {
          if (
            confirm(
              'You are about to set this "email" as the primary contact for this lead. This action will add this lead to the list of other existing leads associated with the same email. \n Are you sure you want to continue?',
            )
          ) {
            additionalContactPrimaryConfirmed(_this);
          } else {
            $('.loader').hide();
          }
        } else {
          additionalContactPrimaryConfirmed(_this);
        }
      },
    });
  } else {
    return false;
  }
});

function additionalContactPrimaryConfirmed(_this) {
  $('.loader').show();
  var id = _this.attr('data-record-id');
  var quote_id = _this.attr('data-quote-id');
  var key = _this.attr('data-key');
  var value = _this.attr('data-value');
  var quote_type = _this.attr('data-quote-type');
  var quote_primary_email_address = _this.attr(
    'data-quote-primary-email-address',
  );
  var quote_customer_id = _this.attr('data-quote-customer-id');

  $.ajax({
    url: '/customer-additional-contact/' + id + '/make-primary',
    method: 'POST',
    data: {
      quote_id: quote_id,
      key: key,
      value: value,
      quote_type: quote_type,
      quote_primary_email_address: quote_primary_email_address,
      quote_customer_id: quote_customer_id,
      _token: $('input[name=_token]').val(),
    },
    success: function (data) {
      $('.loader').hide();
      if (data.data.message) {
        alert(data.data.message);
      } else {
        alert('Primary Email Updated');
      }
      location.reload();
    },
  });
}

// Delete additional email
$('.additional-contact-delete-btn').on('click', function () {
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
$('#additional-contact-modal-add-btn').on('click', function () {
  var additional_mobile_no_reg_exp = new RegExp('[a-zA-Z]');
  var additional_contact_type = $('#additional_contact_type').val();
  var additional_contact_val = $('#additional_contact').val();
  var quote_id = $(this).attr('data-quote-id');
  var quote_type = $(this).attr('data-quote-type');
  var customer_id = $(this).attr('data-customer-id');
  var contact_type_email_enum = $(this).attr('data-contact-type-email-enum');
  var contact_type_mobile_no_enum = $(this).attr(
    'data-contact-type-mobile-no-enum',
  );

  if (
    additional_contact_type == contact_type_email_enum &&
    is_valid_email(additional_contact_val) === false
  ) {
    validation_div_text(
      '#additional-contact-modal-validation-msg',
      'Please enter a valid email address.',
      'red',
    );
    return false;
  }
  if (
    additional_contact_type == contact_type_mobile_no_enum &&
    (additional_contact_val == '' ||
      additional_mobile_no_reg_exp.test(additional_contact_val))
  ) {
    validation_div_text(
      '#additional-contact-modal-validation-msg',
      'Please enter a valid  mobile number.',
      'red',
    );
    return false;
  } else {
    $('.loader').show();
    validation_div_text('#additional-contact-modal-validation-msg', '', '');
    $.ajax({
      url: '/customer-additional-contact/add',
      method: 'POST',
      data: {
        quote_id: quote_id,
        customer_id: customer_id,
        key: additional_contact_type,
        value: additional_contact_val,
        quote_type: quote_type,
        _token: $('input[name=_token]').val(),
      },
      success: function (data) {
        $('.loader').hide();
        if (data.error && data.error.message) {
          validation_div_text(
            '#additional-contact-modal-validation-msg',
            data.error.message,
            'red',
          );
        } else if (data.data.message) {
          alert('Contact Added.');
          location.reload();
        } else {
          validation_div_text(
            '#additional-contact-modal-validation-msg',
            'There was an error!',
            'red',
          );
        }
      },
      error: function (err) {
        $('.loader').hide();
        var errors = err.responseJSON.errors;
        $.each(errors, function (key, value) {
          validation_div_text(
            '#additional-contact-modal-validation-msg',
            value,
            'red',
          );
        });
      },
    });
  }
});

// Make additional mobile_no primary
$('.additional-mobile-no-make-primary-btn').on('click', function () {
  if (confirm('Are you sure to make this primary mobile no.?')) {
    $('.loader').show();
    $.ajax({
      url: '/customer-additional-contact/' + 0 + '/make-primary',
      method: 'POST',
      data: {
        quote_id: $(this).attr('data-quote-id'),
        key: $(this).attr('data-key'),
        value: $(this).attr('data-value'),
        quote_type: $(this).attr('data-quote-type'),
        quote_primary_mobile_no: $(this).attr('data-quote-primary-mobile-no'),
        quote_customer_id: $(this).attr('data-quote-customer-id'),
        _token: $('input[name=_token]').val(),
      },
      success: function (data) {
        $('.loader').hide();
        if (data.data.message) {
          alert(data.data.message);
        } else {
          alert('Primary Mobile Updated');
        }
        location.reload();
      },
    });
  } else {
    return false;
  }
});

function is_valid_email(email) {
  var email_regex =
    /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  return email_regex.test(email);
}

function validation_div_text(id, text, color) {
  $(id)
    .text(text)
    .attr('style', 'color:' + color);
}

function triggerTest() {
  console.log('Triggered');
}

var amlDetailKycLogsDatatable = $('.aml-detail-data-table').DataTable({
  ordering: false,
  info: true,
  searching: false,
  bLengthChange: false,
  serverSide: true,
  processing: true,
  ajax: {
    url: config.routes.aml_kyc_logs_datatable_route,
    data: {
      quote_type_id: $(location).attr('href').split('/').splice(5)[0],
      quote_request_id: $(location).attr('href').split('/').splice(5)[2],
    },
  },
  columns: [
    {
      data: 'id',
      name: 'id',
      render: function (data, type, row) {
        return (
          "<a href='" +
          config.routes.aml_datatable_route +
          '/' +
          row.id +
          "'>" +
          row.id +
          '</a>'
        );
      },
    },
    { data: 'input', name: 'input' },
    { data: 'search_type', name: 'search_type' },
    {
      data: 'screenshot',
      name: 'screenshot',
      render: function (data) {
        var imgSrc = data;
        if (imgSrc != null) {
          return (
            '<a href="' +
            imgSrc +
            '" target="_blank">' +
            '<img class="img-responsive" src="' +
            imgSrc +
            '" alt="screenshot" height="80px" width="80px"></a>'
          );
        }
      },
    },
    {
      data: 'match_found',
      name: 'match_found',
      render: function (data, type, row) {
        return row.match_found > 0 ? 'True' : 'False';
      },
    },
    { data: 'results_found', name: 'results_found' },
    { data: 'created_at', name: 'created_at' },
    { data: 'updated_at', name: 'updated_at' },
  ],
});

$(window).on('load', function () {
  if ($('table').hasClass('aml-detail-data-table')) {
    $('.loader').show();
    amlDetailKycLogsDatatable.draw();
    setTimeout(() => {
      $('.loader').hide();
    }, 1000);
  }
});
