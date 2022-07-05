$(document).ready(function () {
    $(".selectpicker").selectpicker();
    $("#datepicker").datepicker({ dateFormat: "yy-mm-dd" });
    $("#datepicker_2").datepicker({ dateFormat: "yy-mm-dd" });
    $("#transapp_start_date").datepicker({ dateFormat: "yy-mm-dd" });
    $("#transapp_stop_date").datepicker({ dateFormat: "yy-mm-dd" });
    $('#dtBasicExample').DataTable();
    $('.dataTables_length').addClass('bs-select');

    $('.js-example-basic-multiple').select2({
        placeholder: 'Select Permissions to assign against role',
        width: '100%',
        allowClear: true
    });
    $('.select-roles').select2({
        width: '100%',
        allowClear: true
    });
    // TM Leads, AML
    $("#enquiry_date, #allocation_date, #tmLeadsStartDate, #tmLeadsEndDate, #amlCreatedStartDate, #amlCreatedEndDate").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: "yy-mm-dd",
        yearRange: "-20:+00"
    });

    $("#dob").datepicker({ // TM Leads
        changeMonth: true,
        changeYear: true,
        dateFormat: "yy-mm-dd",
        yearRange: "-80:+00"
    });
    $("input[type^=date]").datepicker({ // TM Leads
        changeMonth: true,
        changeYear: true,
        dateFormat: "yy-mm-dd",
        yearRange: "-80:+00"
    });

    if (window.location.href.indexOf('tmleads') > -1) {
        if (performance.navigation.type == 2) {
            location.reload(true);
        }
    }

    $("#next_followup_date_field > #next_followup_date").daterangepicker({ // TM Leads
        timePicker: true,
        singleDatePicker: true,
        timePicker24Hour: true,
        locale: {
            format: 'YYYY-MM-DD HH:mm:ss'
        }
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
        }
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
                type: "post",
                headers: {
                    'x-api-token': config.routes.valuation_api_token,
                },
                data: { carModelDetailId: carTrim, yearOfManufacture: yom },
                success: function (response) {
                    var html = '';
                    response.forEach(element => {
                        html += '<tr><td>' + element.providerName + '</td><td>' + Number(element.carValue) + '</td><td>' + Number(element.carValueUpperLimit) + '</td><td>' + Number(element.carValueLowerLimit) + '</td></tr>';
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
    $("#editor1").markdownEditor({
        preview: true,
        fullscreen: false,
        onPreview: function (content, callback) {
            callback(marked(content));
        },
    });

    $("#editor2").markdownEditor({
        preview: true,
        fullscreen: false,
        onPreview: function (content, callback) {
            callback(marked(content));
        },
    });

    $("#editor3").markdownEditor({
        preview: true,
        fullscreen: false,
        onPreview: function (content, callback) {
            callback(marked(content));
        },
    });

    $("#editor4").markdownEditor({
        preview: true,
        fullscreen: false,
        onPreview: function (content, callback) {
            callback(marked(content));
        },
    });
    $("#datatable").DataTable().destroy();
    $("#datatable").DataTable({
        // "paging": false,
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        scrollX: true,
    });

    $(".datatable-show").DataTable().destroy();
    $(".datatable-show").DataTable({
        // "paging": false,
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        scrollX: true,
    });

    $("#addon-datatable").DataTable().destroy();
    $("#addon-datatable").DataTable({
        // "paging": false,
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        scrollX: true,
    });

    $(".data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        ajax: config.routes.partner_datatable_route,
        columns: [{
            data: "id",
            name: "id",
            render: function (data, type, row) {
                return (
                    "<a href='" +
                    config.routes.partner_datatable_route +
                    "/" +
                    row.id +
                    "'>" +
                    row.id +
                    "</a>"
                );
            },
        },
        { data: "name", name: "name" },
        { data: "name_ar", name: "name_ar" },
        {
            data: "logo_image",
            name: "logo_image",
            render: function (data, type, row, meta) {
                var imgsrc = config.image_path + data; // here data should be in base64 string
                return (
                    '<img class="img-responsive" src="' +
                    imgsrc +
                    '" alt="logo_image" height="40px" width="40px">'
                );
            },
        },
        { data: "is_active", name: "is_active" },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
        ],
    });

    var rewardCategory = $(".reward-category-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        ajax: config.routes.reward_categories_datatable_route,
        columns: [{
            data: "id",
            name: "id",
            render: function (data, type, row) {
                return (
                    "<a href='" +
                    config.routes.reward_categories_datatable_route +
                    "/" +
                    row.id +
                    "'>" +
                    row.id +
                    "</a>"
                );
            },
        },
        { data: "text", name: "text" },
        { data: "text_ar", name: "text_ar" },
        { data: "sort_order", name: "sort_order" },
        { data: "is_active", name: "is_active" },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
        ],
    });

    var rewardTags = $(".reward-tag-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        ajax: config.routes.reward_tags_datatable_route,
        columns: [{
            data: "id",
            name: "id",
            render: function (data, type, row) {
                return (
                    "<a href='" +
                    config.routes.reward_tags_datatable_route +
                    "/" +
                    row.id +
                    "'>" +
                    row.id +
                    "</a>"
                );
            },
        },
        { data: "text", name: "text" },
        { data: "text_ar", name: "text_ar" },
        { data: "sort_order", name: "sort_order" },
        { data: "is_active", name: "is_active" },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
        ],
    });

    $(".reward-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        ajax: config.routes.reward_datatable_route,
        columns: [{
            data: "id",
            name: "id",
            render: function (data, type, row) {
                return (
                    "<a href='" +
                    config.routes.reward_datatable_route +
                    "/" +
                    row.id +
                    "'>" +
                    row.id +
                    "</a>"
                );
            },
        },
        { data: "coupon_code", name: "coupon_code" },
        { data: "partner", name: "partner" },
        { data: "discount", name: "discount" },
        { data: "start_date", name: "start_date" },
        { data: "end_date", name: "end_date" },
        { data: "is_active", name: "is_active" },
        ],
    });

    var usersDataTable = $(".user-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: {
            url: config.routes.user_datatable_route,
            data: function (d) {
                d.email = $("#users_email").val();
                d.name = $("#users_name").val();
            }
        },
        columns: [{
            data: "id",
            name: "id",
            render: function (data, type, row) {
                return (
                    "<a href='" +
                    config.routes.user_datatable_route +
                    "/" +
                    row.id +
                    "'>" +
                    row.id +
                    "</a>"
                );
            },
        },
        { data: "name", name: "name" },
        { data: "email", name: "email" },
        { data: "roles", name: "roles" },
        { data: "teamName", name: "teamName" },
        {
            data: "is_active", name: "is_active",
            render: function (data, type, row) {
                return data == 1 ? "Active" : "InActive";
            },
        },
        { data: 'created_at', name: 'created_at' },
        { data: 'updated_at', name: 'updated_at' },
        ],
    });

    $(".role-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.role_datatable_route,
        columns: [{
            data: "id",
            name: "id",
            render: function (data, type, row) {
                return (
                    "<a href='" +
                    config.routes.role_datatable_route +
                    "/" +
                    row.id +
                    "'>" +
                    row.id +
                    "</a>"
                );
            },
        },
        { data: "name", name: "name" },
        { data: 'created_at', name: 'created_at' },
        { data: 'updated_at', name: 'updated_at' },
        ],
    });

    $(".insurancecompany-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.insurancecompany_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.insurancecompany_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: "name", name: "name" },
        { data: "is_active", name: "is_active" },
        { data: "created_by", name: "created_by" },
        { data: "updated_by", name: "updated_by" },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
        ],
    });

    $(".handler-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.handler_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.handler_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: "name", name: "name" },
        { data: "is_active", name: "is_active" },
        { data: "created_by", name: "created_by" },
        { data: "updated_by", name: "updated_by" },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
        ],
    });

    $(".status-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.status_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.status_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: "name", name: "name" },
        { data: "is_active", name: "is_active" },
        { data: "created_by", name: "created_by" },
        { data: "updated_by", name: "updated_by" },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
        ],
    });

    var transactionsDatatable = $('.transaction-data-table').DataTable({
        dom: 'Bfrtip',
        "buttons": [{
            "extend": 'csv',
            "text": '<i class="fa fa-download" style="color:orange;" id="transapp-export"></i><div id="transapp-export-text" class="required" style="font-weight:bold;"></div>',
            "titleAttr": 'Download CSV',
            "action": newexportaction
        }],
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
                d.transapp_start_date = $("#transapp_start_date").val();
                d.transapp_stop_date = $("#transapp_stop_date").val();
                d.transactor = $("#transactor_value").val();
                d.handler = $("#handler_value").val();
                d.insurance_company = $("#insurance_company_value").val();
                d.reason = $("#reason_value").val();
                d.payment_mode = $("#payment_mode_value").val();
                d.transapp_approval_code = $("#transapp_approval_code").val();
                d.transapp_customer_email = $("#customer_email").val();
                d.transapp_customer_name = $("#customer_name").val();
            },
        },
        columns: [
            { data: "approval_code", name: "approval_code" },
            { data: "created_at", name: "created_at" },
            { data: "insurance", name: "insurance" },
            { data: "amount_paid", name: "amount_paid" },
            { data: "customer_name", name: "customer_name" },
            { data: "risk_details", name: "risk_details" },
            { data: "created_by_name", name: "created_by_name" },
            { data: "handler_name", name: "handler_name" },
            { data: "payment_mode", name: "payment_mode" },
            { data: "prev_approval_code", name: "prev_approval_code" },
        ]
    });

    $("#transapp-export").hide();
    $("#search-transactions").submit(function (e) {
        e.preventDefault();
        var tmLeadsStartDate = $("#transapp_start_date");
        var tmLeadsEndDate = $("#transapp_stop_date");
        var message = $("#message");
        var transappExport = $("#transapp-export");

        var tmLeadsStartDateVal = tmLeadsStartDate.val();
        var tmLeadsEndDateVal = tmLeadsEndDate.val();

        var tmLeadsStartDateVar = new Date(tmLeadsStartDateVal);
        var tmLeadsEndDateVar = new Date(tmLeadsEndDateVal);
        var timeDiff = tmLeadsEndDateVar.getTime() - tmLeadsStartDateVar.getTime();
        var daysDiff = timeDiff / (1000 * 60 * 60 * 24);

        if ((tmLeadsStartDateVal == "") || (tmLeadsEndDateVal == "")) {
            message.html("Please select start & stop dates");
            tmLeadsStartDate.css('border-color', 'red');
            tmLeadsEndDate.css('border-color', 'red');
            transappExport.hide();
            return false
        }
        if (tmLeadsStartDateVal > tmLeadsEndDateVal) {
            message.html("Start date must be equal or less than stop date");
            tmLeadsStartDate.css('border-color', 'red');
            tmLeadsEndDate.css('border-color', 'red');
            transappExport.hide();
            return false
        }
        if (daysDiff > 30) {
            message.html("Allowed number of days between start & stop dates are 30 days.");
            tmLeadsStartDate.css('border-color', 'red');
            tmLeadsEndDate.css('border-color', 'red');
            transappExport.hide();
            return false
        }
        else {
            transactionsDatatable.draw();
            $(".loader").show();
            message.html("");
            tmLeadsStartDate.css('border-color', '');
            tmLeadsEndDate.css('border-color', '');
            transappExport.show();
            setTimeout(() => {
                $(".loader").hide();
            }, 1000);

            var isTransappAdmin = $("#isTransappAdmin").val();
            if (isTransappAdmin == 1) {
                $("a[title='Download CSV']").show();
            } else {
                $("a[title='Download CSV']").hide();
            }
        }
    });

    $("#transapp-export").click(function () {
        console.log('clicked on export-export');
        $("#transapp-export").hide();
        $("#transapp-export-text").text("Please wait until csv file will be downloaded. More waiting time is depending on number of records.");
        $('#transapp-export-text').show().delay(10000).fadeOut();
    });

    $("#search-users").submit(function (e) {
        e.preventDefault();
        $(".loader").show();
        usersDataTable.draw();
        setTimeout(() => {
            $(".loader").hide();
        }, 1000);
    });

    $(".reason-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.reason_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.reason_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: "name", name: "name" },
        { data: "is_active", name: "is_active" },
        { data: "created_by", name: "created_by" },
        { data: "updated_by", name: "updated_by" },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
        ],
    });

    $(".paymentmode-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.paymentmode_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.paymentmode_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: "name", name: "name" },
        { data: "is_active", name: "is_active" },
        { data: "created_by", name: "created_by" },
        { data: "updated_by", name: "updated_by" },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
        ],
    });

    var carquoteDatatable = $(".carquote-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: {
            url: config.routes.carquote_datatable_route,
            data: function (d) {
                d.searchtype = $("#search_type").val();
                d.searchfield = $("input[name=searchfield]").val();
                d.quotestatus = $("#quote_status_value").val();
                d.paymentstatus = $("#payment_status_value").val();
            },
        },
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.carquote_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: "car_value", name: "car_value" },
        { data: "is_synced", name: "is_synced" },
        { data: "device", name: "device" },
        { data: "code", name: "code" },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
        ],
    });

    $("#search-car-quote").submit(function (e) {
        e.preventDefault();
        $(".loader").show();
        carquoteDatatable.draw();
        setTimeout(() => {
            $(".loader").hide();
        }, 1000);
    });

    $("#resubmit_api_carquote").click(function () {
        $(".loader").show();
        $("#resubmit_api_carquote").attr("disabled", true);
        var data_id = $(this).attr("data-id");
        var carQuotes = [];
        if (data_id != "") carQuotes.push(data_id);
        $(".multicheckbox").each(function (index) {
            if ($(this).prop("checked")) {
                var id = $(this).attr("data-id");
                carQuotes.push(id);
            }
        });
        if (carQuotes.length > 0) {
            $.ajax({
                url: config.routes.carquote_resubmitap_route,
                type: "post",
                data: { car_quotes: carQuotes, _token: config._token },
                success: function (response) {
                    $(".loader").hide();
                    $("#success_message").show();
                    $("#success_message")
                        .fadeIn()
                        .html("Resubmit Api Execution is successfull");

                    $(".carquote-data-table").DataTable().ajax.reload();

                    setTimeout(function () {
                        $("#resubmit_api_carquote").attr("disabled", false);
                        $("#success_message").fadeOut("slow");
                    }, 3000);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    $(".loader").hide();
                    $("#error_message").show();
                    $("#error_message").fadeIn().html(errorThrown);
                    setTimeout(function () {
                        $("#resubmit_api_carquote").attr("disabled", false);
                        $("#error_message").fadeOut("slow");
                    }, 3000);
                },
            });
        } else {
            $(".loader").hide();
            $("#error_message").show();
            $("#error_message").fadeIn().html("Please select atleast one row");
            setTimeout(function () {
                $("#resubmit_api_carquote").attr("disabled", false);
                $("#error_message").fadeOut("slow");
            }, 3000);
        }
    });

    $(".healthquote-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.healthquote_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.healthquote_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: "preference", name: "preference" },
        { data: "is_synced", name: "is_synced" },
        { data: "device", name: "device" },
        { data: "code", name: "code" },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
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
                d.searchtype = $("#search_type").val();
                d.searchfield = $("input[name=searchfield]").val();
                d.claimstatus = $("#claim_status_value").val();
                d.assignedto = $("#assigned_to_value").val();
                d.type_of_insurance = $("#type_of_insurance_value").val();
            },
        },
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.claim_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
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
        ]
    });

    $("#search-claims").submit(function (e) {
        e.preventDefault();
        $(".loader").show();
        claimsDatatable.draw();
        setTimeout(() => {
            $(".loader").hide();
        }, 1000);
    });

    $('.typeofinsurance-data-table').DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.typeofinsurance_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.typeofinsurance_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: 'text', name: 'text' },
        { data: 'text_ar', name: 'text_ar' },
        { data: 'sort_order', name: 'sort_order' },
        { data: 'is_active', name: 'is_active' },
        { data: 'created_at', name: 'created_at' },
        { data: 'updated_at', name: 'updated_at' },
        ]
    });

    $('.vehicledepreciation-data-table').DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.vehicledepreciation_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.vehicledepreciation_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
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
        ]
    });

    $('.subtypeofinsurance-data-table').DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.subtypeofinsurance_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.subtypeofinsurance_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: 'text', name: 'text' },
        { data: 'text_ar', name: 'text_ar' },
        { data: 'sort_order', name: 'sort_order' },
        { data: 'is_active', name: 'is_active' },
        { data: 'created_at', name: 'created_at' },
        { data: 'updated_at', name: 'updated_at' },
        ]
    });

    $('.claimsstatus-data-table').DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.claimsstatus_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.claimsstatus_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: 'text', name: 'text' },
        { data: 'text_ar', name: 'text_ar' },
        { data: 'sort_order', name: 'sort_order' },
        { data: 'is_active', name: 'is_active' },
        { data: 'created_at', name: 'created_at' },
        { data: 'updated_at', name: 'updated_at' },
        ]
    });

    $('.carrepaircoverage-data-table').DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.carrepaircoverage_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.carrepaircoverage_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: 'text', name: 'text' },
        { data: 'text_ar', name: 'text_ar' },
        { data: 'sort_order', name: 'sort_order' },
        { data: 'is_active', name: 'is_active' },
        { data: 'created_at', name: 'created_at' },
        { data: 'updated_at', name: 'updated_at' },
        ]
    });

    $('.carrepairtype-data-table').DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.carrepairtype_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.carrepairtype_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: 'text', name: 'text' },
        { data: 'text_ar', name: 'text_ar' },
        { data: 'sort_order', name: 'sort_order' },
        { data: 'is_active', name: 'is_active' },
        { data: 'created_at', name: 'created_at' },
        { data: 'updated_at', name: 'updated_at' },
        ]
    });

    $('#car_make_id').on('change', function (e) {
        var make_code = $("#car_make_id option:selected").attr('data-id');
        if (!make_code) {
            make_code = $("#car_make_id option:selected").val();
        }
        $.get('/car-model?make_code=' + make_code, function (data) {
            var carmodel = $('#car_model_id').empty();
            carmodel.append('<option data-id="" value="">Please Confirm Car Model</option>');
            $.each(data, function (create, carmodelObj) {
                var option = $('<option/>', { id: create, value: carmodelObj });
                carmodel.append('<option data-id="' + carmodelObj.code + '" value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
            });
        });
    });

    // Claims-EditView: Populate models of selected car make
    // var make_code = $("#edit_car_make_model #car_make_id option:selected").attr('data-id');
    // var old_car_model_id = $("#edit_car_make_model #old_car_model_id").val();
    // $.get('/car-model?make_code=' + make_code, function (data) {
    //     var carmodel = $('#edit_car_make_model #car_model_id').empty();
    //     $.each(data, function (create, carmodelObj) {
    //         var option = $('<option/>', { id: create, value: carmodelObj });
    //         if (old_car_model_id == carmodelObj.id)
    //             carmodel.append('<option selected data-id="' + carmodelObj.code + '" value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
    //         else
    //             carmodel.append('<option  data-id="' + carmodelObj.code + '" value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
    //     });
    // });

    $("#sub_type_of_insurance").hide();
    $("#car_fields").hide();
    type_of_insurance_fields_visibility();
    $('#type_of_insurances_id').on('change', function (e) {
        type_of_insurance_fields_visibility();
    });

    function type_of_insurance_fields_visibility() {
        var type_of_insurance_text = $("#type_of_insurances_id option:selected").attr('data-id');
        if (type_of_insurance_text == 'Business') {
            $("#sub_type_of_insurance").show();
        } else {
            $("#sub_type_of_insurance").hide();
        }
        if (type_of_insurance_text == 'Car') {
            $("#car_fields").show();
        } else {
            $("#car_fields").hide();
        }
    }

    var customerDataTable = $(".customer-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: {
            url: config.routes.customer_data_table_route,
            data: function (d) {
                d.searchtype = $("#search_type").val();
                d.searchfield = $("#searchfield").val();
            },
        },
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.customer_data_table_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: "first_name", name: "first_name" },
        { data: "email", name: "email" },
        { data: "mobile_no", name: "mobile_no" },
        { data: "gender", name: "gender" },
        { data: "has_alfred_access", name: "has_alfred_access" },
        { data: "dob", name: "dob", orderable: false, searchable: false },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
        ],
    });

    var vehicleTypeDataTable = $(".base-discount-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: {
            url: config.routes.discount_base_data_table_route,
            data: function (d) {
                d.vehicle_type = $("#vehicle_type").val();
            },
        },
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.discount_base_data_table_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: "value_start", name: "value_start" },
        { data: "value_end", name: "value_end" },
        { data: "vehicle_type_text", name: "vehicle_type_text" },
        { data: "comprehensive_discount", name: "comprehensive_discount" },
        { data: "agency_discount", name: "agency_discount" },
        { data: "is_active", name: "is_active", orderable: false, searchable: false },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
        ],
    });

    var ageDiscountDataTable = $(".age-discount-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: {
            url: config.routes.age_discount_datatable_route,
        },
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.age_discount_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: "age_start", name: "age_start" },
        { data: "age_end", name: "age_end" },
        { data: "discount", name: "discount" },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
        ],
    });

    $("#search-customer").submit(function (e) {
        e.preventDefault();
        $(".loader").show();
        customerDataTable.draw();
        setTimeout(() => {
            $(".loader").hide();
        }, 1000);
    });

    $("#search-vehicleTypes").submit(function (e) {
        e.preventDefault();
        $(".loader").show();
        vehicleTypeDataTable.draw();
        setTimeout(() => {
            $(".loader").hide();
        }, 1000);
    });

    $("#search-vehicleTypes-reset").click(function (e) {
        e.preventDefault();
        $(".loader").show();
        $("#vehicle_type").val($("#vehicle_type option:first").val());
        vehicleTypeDataTable.draw();
        setTimeout(() => {
            $(".loader").hide();
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
            data: function (d) {
                d.searchType = $("#searchType").val();
                d.searchField = $("input[name=searchField]").val();
                d.quoteType = $("#quoteTypeValue").val();
                d.matchFound = $("#matchFound").val();
                d.amlCreatedStartDate = $("#amlCreatedStartDate").val();
                d.amlCreatedEndDate = $("#amlCreatedEndDate").val();
            },
        },
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.aml_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: "quote_type_text", name: "quote_type_text" },
        {
            data: 'quote_request_id',
            name: 'quote_request_id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.aml_datatable_route + '/' + row.quote_type_id + '/details/' + row.quote_request_id + "'>" + row.cdb_id + "</a>"
            }
        },
        { data: "input", name: "input" },
        {
            data: "screenshot",
            name: "screenshot",
            render: function (data, type, row, meta) {
                var imgSrc = data;
                if (imgSrc != null) {
                    return (
                        '<a href="' + imgSrc + '" target="_blank">' +
                        '<img class="img-responsive" src="' + imgSrc + '" alt="screenshot" height="80px" width="80px"></a>'
                    );
                }
            },
        },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },
        ]
    });

    $("#aml-search-submit").hide();
    $("#aml-search-fields").hide();
    $('#quoteTypeValue').on("change", function (e) {
        aml_search_filters_visiblity();
    });

    function aml_search_filters_visiblity() {
        var quoteTypeValue = $("#quoteTypeValue").val();
        console.log("quoteTypeValue1: ", quoteTypeValue);
        if (quoteTypeValue != "") {
            $("#aml-search-fields").show(300);
            $("#aml-search-submit").show(300);
        } else {
            $("#aml-search-fields").hide(300);
            $("#aml-search-submit").hide(300);
        }
    }

    $("#searchAML").submit(function (e) {
        var amlCreatedStartDate = $("#amlCreatedStartDate").val();
        var amlCreatedEndDate = $("#amlCreatedEndDate").val();
        var searchType = $("#searchType").val();

        if (searchType == "" && (amlCreatedStartDate == "" || amlCreatedEndDate == "")) {
            $("#amlCreatedStartDateMsg").html("Please select start & end dates");
            $('#amlCreatedStartDate').css('border-color', 'red');
            $('#amlCreatedEndDate').css('border-color', 'red');
            return false
        }
        else if (amlCreatedStartDate != "" && amlCreatedEndDate != "") {
            var amlCreatedStartDateSet = new Date(amlCreatedStartDate);
            var amlCreatedEndDateSet = new Date(amlCreatedEndDate);

            var amlCreatedStartEndTimeDifference = amlCreatedEndDateSet.getTime() - amlCreatedStartDateSet.getTime();
            var amlCreatedStartEndDaysDiff = amlCreatedStartEndTimeDifference / (1000 * 60 * 60 * 24);

            if (amlCreatedStartEndDaysDiff > 30) {
                $("#amlCreatedStartDateMsg").html("Allowed no. of days between start & end dates are 30 days.");
                $('#amlCreatedStartDate').css('border-color', 'red');
                $('#amlCreatedEndDate').css('border-color', 'red');
                $("#amlCreatedEndDateMsg").html("");
                return false;
            }
            else if (amlCreatedStartDate > amlCreatedEndDate) {
                $("#amlCreatedStartDateMsg").html("Start date must be equal or less than end date");
                $('#amlCreatedStartDate').css('border-color', 'red');
                $('#amlCreatedEndDate').css('border-color', 'red');
                $("#amlCreatedEndDateMsg").html("");
                return false;
            }
            else {
                $("#amlCreatedStartDateMsg").html("");
                $("#amlCreatedEndDateMsg").html("");
                $('#amlCreatedStartDate').css('border-color', '');
                $('#amlCreatedEndDate').css('border-color', '');
                e.preventDefault();
                $(".loader").show();
                amlDatatable.draw();
                setTimeout(() => {
                    $(".loader").hide();
                }, 1000);
            }
        }
        else {
            $("#amlCreatedStartDateMsg").html("");
            $("#amlCreatedEndDateMsg").html("");
            $('#amlCreatedStartDate').css('border-color', '');
            $('#amlCreatedEndDate').css('border-color', '');
            e.preventDefault();
            $(".loader").show();
            amlDatatable.draw();
            setTimeout(() => {
                $(".loader").hide();
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
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.tminsurancetype_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: 'code', name: 'code' },
        { data: 'text', name: 'text' },
        { data: 'text_ar', name: 'text_ar' },
        { data: 'sort_order', name: 'sort_order' },
        { data: 'is_active', name: 'is_active' },
        { data: 'created_at', name: 'created_at' },
        { data: 'updated_at', name: 'updated_at' },
        ]
    });

    $('.tmcallstatus-data-table').DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.tmcallstatus_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.tmcallstatus_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: 'code', name: 'code' },
        { data: 'text', name: 'text' },
        { data: 'text_ar', name: 'text_ar' },
        { data: 'sort_order', name: 'sort_order' },
        { data: 'is_active', name: 'is_active' },
        { data: 'created_at', name: 'created_at' },
        { data: 'updated_at', name: 'updated_at' },
        ]
    });

    $('.tmleadstatus-data-table').DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.tmleadstatus_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.tmleadstatus_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: 'code', name: 'code' },
        { data: 'text', name: 'text' },
        { data: 'text_ar', name: 'text_ar' },
        { data: 'sort_order', name: 'sort_order' },
        { data: 'is_active', name: 'is_active' },
        { data: 'created_at', name: 'created_at' },
        { data: 'updated_at', name: 'updated_at' },
        ]
    });

    var tmLeadsDatatable = $('.tmlead-data-table').DataTable({
        dom: 'Bfrtip',
        "buttons": [{
            "extend": 'csv',
            "text": '<i class="fa fa-download" style="color:orange;" id="tm-leads-export"></i><div id="tm-leads-export-text" class="required" style="font-weight:bold;"></div>',
            "titleAttr": 'Download CSV',
            "action": newexportaction
        }],
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
                d.searchType = $("#searchType").val();
                d.searchField = $("input[name=searchField]").val();
                d.assigned_to_id = $("#assigned_to_id").val();
                d.tm_insurance_types_id = $("#tm_insurance_types_id").val();
                d.tm_lead_types_id = $("#tm_lead_types_id").val();
                d.tm_lead_statuses_id = $("#tm_lead_statuses_id").val();
                d.tmLeadsStartDate = $("#tmLeadsStartDate").val();
                d.tmLeadsEndDate = $("#tmLeadsEndDate").val();
            },
        },
        columns: [{
            data: "id",
            name: "id",
            render: function (data, type, row, meta) {
                var isCurrentUserIsAdvisor = $("#isCurrentUserIsAdvisor").val();
                if (isCurrentUserIsAdvisor == 0) {
                    return (
                        '<input type="checkbox" id="tmLeadID" class="tmleadCheckbox" name="tmLeadID" value="' + data + '">'
                    );
                }
            },
        },
        {
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.tmlead_datatable_route + '/' + row.id + "'>" + row.cdb_id + "</a>"
            }
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
        { data: 'tm_updated_at', name: 'updated_at' }

        ],
        createdRow: function (row, data, index) {

            if (data.tm_lead_status_code == "NoAnswer" || data.tm_lead_status_code == "SwitchedOff" ||
                data.tm_lead_status_code == "PipelineNoInfo" || data.tm_lead_status_code == "PipelineImmediate" ||
                data.tm_lead_status_code == "PipelineFuture" || data.tm_lead_status_code == "DealingWithAnAdvisor") {

                var d = new Date();
                var currentTimestmap = d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2) +
                    " " + ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2) + ":" + ("0" + d.getSeconds()).slice(-2);

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

                    $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                        $.fn.dataTable.ext.buttons.csvHtml5.action.call(self, e, dt, button, config) :
                        $.fn.dataTable.ext.buttons.csvFlash.action.call(self, e, dt, button, config);
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
    };

    $("#tm-leads-export").click(function () {
        $("#tm-leads-export").hide();
        $("#tm-leads-export-text").text("Please wait until csv file will be downloaded. More waiting time is depending on number of records.");
        $('#tm-leads-export-text').show().delay(10000).fadeOut();
    });

    $("#tm-leads-upload-csv-button").click(function () {
        $("#tm-leads-upload-csv-button").hide();
        $("#tm-leads-upload-csv-button-text").text("Please wait until csv file will be uploaded. More waiting time is depending on number of records.");
    });

    // TM Leads: Search button trigger
    $("#tm-leads-export").hide();
    $("#search-tm-leads").submit(function (e) {
        e.preventDefault();
        var tmLeadsStartDate = $("#tmLeadsStartDate").val();
        var tmLeadsEndDate = $("#tmLeadsEndDate").val();
        var searchType = $("#searchType").val();

        if (searchType == "created_at" || searchType == "updated_at" || searchType == "next_followup_date" ||
            searchType == "enquiry_date" || searchType == "allocation_date") {

            var tmLeadsStartDateVar = new Date(tmLeadsStartDate);
            var tmLeadsEndDateVar = new Date(tmLeadsEndDate);
            var time_difference = tmLeadsEndDateVar.getTime() - tmLeadsStartDateVar.getTime();
            var daysDiff = time_difference / (1000 * 60 * 60 * 24);

            if ((tmLeadsStartDate == "") || (tmLeadsEndDate == "")) {
                $("#result").html("Please select start & end dates");
                $('#tmLeadsStartDate').css('border-color', 'red');
                $('#tmLeadsEndDate').css('border-color', 'red');
                $("#tm-leads-export").hide();
                return false
            } else if (tmLeadsStartDate > tmLeadsEndDate) {
                $("#result").html("Start date must be equal or less than end date");
                $('#tmLeadsStartDate').css('border-color', 'red');
                $('#tmLeadsEndDate').css('border-color', 'red');
                $("#tm-leads-export").hide();
                return false
            } else if (daysDiff > 30) {
                $("#result").html("Allowed number of days between start and and dates are 30 days.");
                $('#tmLeadsStartDate').css('border-color', 'red');
                $('#tmLeadsEndDate').css('border-color', 'red');
                $("#tm-leads-export").hide();
                return false
            } else {
                tmLeadsDatatable.draw();
                $(".loader").show();
                $("#result").html("");
                $('#tmLeadsStartDate').css('border-color', '');
                $('#tmLeadsEndDate').css('border-color', '');
                $("#tm-leads-export").show();
                setTimeout(() => {
                    $(".loader").hide();
                }, 1000);

                // Hide Download CSV for advisors
                var isCurrentUserIsAdvisor = $("#isCurrentUserIsAdvisor").val();
                if (isCurrentUserIsAdvisor == 0) {
                    $("a[title='Download CSV']").show();
                } else {
                    $("a[title='Download CSV']").hide();
                }
            }
        } else {
            tmLeadsDatatable.draw();
            $(".loader").show();
            $("#result").html("");
            $('#tmLeadsStartDate').css('border-color', '');
            $('#tmLeadsEndDate').css('border-color', '');
            $("#tm-leads-export").hide();
            setTimeout(() => {
                $(".loader").hide();
            }, 1000);
        }

    });

    // TM Leads: Select tm leads id and store in hidden field
    $("#checkAllTmLeads").click(function () {
        $('input:checkbox').not(this).prop('checked', this.checked);
        $('#selectTmLeadId').val('');
        var idsArray = $('#selectTmLeadId').val();
        $('input:checkbox').each(function (i, item) {
            idsArray = idsArray + $(item).val() + ',';
        });
        $('#selectTmLeadId').val(idsArray.replace(/^,|,$/g, ''));
        if ($(this).is(":checked")) {
            $("#tm-leads-assign-div").show(300);
        } else {
            $("#tm-leads-assign-div").hide(200);
        }
    });

    $('#assign_team').on('change', function () {
        if ($(this).val() == "GM") {
            $('#assigned_to_id_new').attr('disabled', true);
        } else {
            $('#assigned_to_id_new').attr('disabled', false);
        }
    });

    // TM Leads: Select tm leads id and store in hidden field
    $("#tmLeadsAssignToUser").click(function () {
        var tmLeadIDs = [];
        if ($('#healthTeamTypeAssignDiv').length > 0) {
            if ($('#assign_team').val() == "") {
                $("#teamErrorSpan").show().fadeOut(5000);
                return false;
            }
        }

        if (!$('#checkAllTmLeads').is(":checked")) {
            $.each($("input[name='tmLeadID']:checked"), function () {
                tmLeadIDs.push($(this).val());
            });
            $('#selectTmLeadId').val(tmLeadIDs);
        }
    });

    $("#assignAfterTeam").click(function () {
        var tmLeadIDs = [];
        if ($('#assign_team').val() == "") {
            $("#teamAssignValidation").show().fadeOut(5000);
            return false;
        }

        if (!$('#checkAllTmLeads').is(":checked")) {
            $.each($("input[name='tmLeadID']:checked"), function () {
                tmLeadIDs.push($(this).val());
            });
            $('#selectTmLeadId').val(tmLeadIDs);
        }
    });

    // TM: Selecting a single record should also enable manual allocation
    $(document).on("change", "#tmLeadID", function () {
        var idsArray = $('#selectTmLeadId').val();
        idsArray = idsArray + ',' + $(this).val() + ',';
        $('#selectTmLeadId').val(idsArray.replace(/^,|,$/g, ''));
        var countSelectedTmLeadIds = document.querySelectorAll('#tmLeadID:checked').length;
        if (countSelectedTmLeadIds > 0) {
            $("#tm-leads-assign-div").show(300);
        } else {
            $('#checkAllTmLeads').prop('checked', false);
            $("#tm-leads-assign-div").hide(300);
        }
    });

    // TM Leads: On check main checkbox, display lead assignment panel
    $("#tm-leads-assign-div").hide();

    // TM Leads: OnClick on phone number ignore redirection
    $("#ignore-redirection").click(function () {
        return false;
    });

    // TM Leads: Display Car fields if insurance type Car is selected
    $("#tm_car_fields").hide();
    $("#tm_dob_field").hide();
    tm_type_of_insurance_fields_visibility();
    $('#tm_insurance_types_id').on('change', function (e) {
        tm_type_of_insurance_fields_visibility();
    });

    function tm_type_of_insurance_fields_visibility() {
        var tm_insurance_types_id_code = $("#tm_insurance_types_id option:selected").attr('data-id');

        if (tm_insurance_types_id_code == 'Car') {
            $("#tm_car_fields").show();
        } else {
            $("#tm_car_fields").hide();
        }

        if (tm_insurance_types_id_code == 'Car' || tm_insurance_types_id_code == 'Bike' ||
            tm_insurance_types_id_code == 'Life' || tm_insurance_types_id_code == 'Health' || tm_insurance_types_id_code == 'Critical') {
            $("#tm_dob_field").show();
        } else {
            $("#tm_dob_field").hide();
        }
    }

    // TM Leads: Display Followup date conditionally
    $("#next_followup_date_field").hide();
    next_followup_date_field_visibility();
    $('#tm_lead_statuses_id').on("change", function (e) {
        next_followup_date_field_visibility();
    });

    function next_followup_date_field_visibility() {
        var tm_lead_status_code = $("#tm_lead_statuses_id option:selected").attr("data-id");

        if (tm_lead_status_code == "NoAnswer" || tm_lead_status_code == "SwitchedOff" ||
            tm_lead_status_code == "PipelineNoInfo" || tm_lead_status_code == "PipelineImmediate" ||
            tm_lead_status_code == "PipelineFuture" || tm_lead_status_code == "DealingWithAnAdvisor" ||
            (tm_lead_status_code == "NewLead" && tmLeadEditFormNextFollowupDate != "")) {
            $("#next_followup_date_field").show();
        } else {
            $("#next_followup_date_field").hide();
        }
    }

    // TM Leads: Display date fields and search value field conditionally
    $("#tmLeads-search-value-filter").show();
    $("#tmLeads-search-start-end-dates-filters").hide();
    $('#searchType').on("change", function (e) {
        tmleads_search_start_end_dates_filters_visiblity();
    });

    function tmleads_search_start_end_dates_filters_visiblity() {
        var searchTypeValue = $("#searchType").val();
        console.log("searchTypeValue: " + searchTypeValue);
        if (searchTypeValue == "created_at" || searchTypeValue == "updated_at" || searchTypeValue == "next_followup_date" ||
            searchTypeValue == "enquiry_date" || searchTypeValue == "allocation_date") {
            $("#tmLeads-search-start-end-dates-filters").show(300);
            $("#tmLeads-search-value-filter").hide(300);
        } else {
            $("#tmLeads-search-start-end-dates-filters").hide(300);
            $("#tmLeads-search-value-filter").show(300);
            $('#tmLeadsStartDate').val('');
            $('#tmLeadsEndDate').val('');
        }
    }

    // TM Leads: OnChange searchType do reset searchField
    $('#search-tm-leads #searchType').on('change', function (e) {
        $("#searchField").val("");
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
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.tmuploadlead_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: 'file_name', name: 'file_name' },
        { data: 'good', name: 'good' },
        { data: 'user_name', name: 'user_name' },
        { data: 'created_at', name: 'created_at' },
        ]
    });

    $('.rewardsliders-data-table').DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        processing: true,
        ajax: config.routes.reward_sliders_datatable_route,
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.reward_sliders_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        {
            data: "image",
            name: "image",
            render: function (data, type, row, meta) {
                var imgsrc = config.image_path_rewards_slider + data;
                return (
                    '<img class="img-responsive" src="' + imgsrc + '" alt="image" height="40px" width="40px">'
                );
            },
        },
        { data: 'link', name: 'link' },
        { data: 'start_date', name: 'start_date' },
        { data: 'end_date', name: 'end_date' },
        { data: 'sort_order', name: 'sort_order' },
        { data: 'is_public', name: 'is_public' },
        { data: 'is_active', name: 'is_active' },
        { data: 'created_at', name: 'created_at' },
        { data: 'updated_at', name: 'updated_at' },
        ]
    });

    $("#rewards_slider_start_end #start_date").daterangepicker({ // Rewards Slider start date & time
        timePicker: true,
        singleDatePicker: true,
        timePicker24Hour: true,
        minDate: new Date(),
        locale: {
            format: 'YYYY-MM-DD HH:mm:ss'
        }
    });

    var rewardSliderEditEndDateTime = $('#rewards_slider_start_end #rewardSliderEditEndDateTime').val();
    var rsdt = new Date();
    var rewardSliderEditCurrentDateTime = rsdt.getFullYear() + "-" + ("0" + (rsdt.getMonth() + 1)).slice(-2) + "-" + ("0" + rsdt.getDate()).slice(-2) +
        " " + ("0" + rsdt.getHours()).slice(-2) + ":" + ("0" + rsdt.getMinutes()).slice(-2) + ":" + ("0" + rsdt.getSeconds()).slice(-2);

    console.log("rewardSliderEditCurrentDateTime: ", rewardSliderEditCurrentDateTime);
    console.log("rewardSliderEditEndDateTime: ", rewardSliderEditEndDateTime);

    if (rewardSliderEditEndDateTime != "" && rewardSliderEditEndDateTime < rewardSliderEditCurrentDateTime) {
        minRewardSliderEndDateTime = rewardSliderEditEndDateTime;
    } else {
        minRewardSliderEndDateTime = new Date();
    }

    $("#rewards_slider_start_end #end_date").daterangepicker({ // Rewards Slider end date & time
        timePicker: true,
        singleDatePicker: true,
        timePicker24Hour: true,
        minDate: minRewardSliderEndDateTime,
        locale: {
            format: 'YYYY-MM-DD HH:mm:ss'
        }
    });

    //select/unselect all checkboxes if this selected
    $("#select_all_checkboxes").click(function (e) {
        var isChecked = e.target.checked;

        if (isChecked === true) {
            $(".multicheckbox").each(function (index) {
                $(this).prop("checked", true);
            });
        } else {
            $(".multicheckbox").each(function (index) {
                $(this).prop("checked", false);
            });
        }
    });

    $('.quotePlanModalPopup').on('click', function (e) {
        e.preventDefault();
        $('.quote-plan-modal-body').load($(this).attr("planDetailUrl"), function () {
            $('#quotePlanModal').modal({ show: true });
        });
    });
    $('#duplicateLeadModalBtn').on('click', function (e) {
        e.preventDefault();
        $('#duplicateLeadModal').modal({ show: true });;
    });
    $('#add-activity-btn').on('click', function () {
        $('#activityModal').modal({ show: true });
    });

    $("#due_date").daterangepicker({
        timePicker: true,
        singleDatePicker: true,
        timePicker24Hour: true,
        locale: {
            format: 'YYYY-MM-DD HH:mm:ss'
        }
    });

    $("#quotePlansGenerateButton").click(function () {
        var quotePlansGenerateUrl = $('#quotePlansGenerateUrl').val();
        navigator.clipboard.writeText(quotePlansGenerateUrl);
        $("#quotePlansGenerateMsg").show(300);
        $("#quotePlansGenerateMsg").hide(2000);
    });

    $(".auditablebtn").click(function () {
        var auditableId = $(this).attr("data-id");
        var auditableType = $(this).attr("data-model");
        $(this).attr("disabled", true);

        $.ajax({
            url: config.routes.load_auditable,
            method: "POST",
            data: { auditableId, auditableType, _token: config._token },
            success: function (data) {
                $("#auditable").html(data);
                $(".auditablebtn").hide();
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
    $("#return_to_view").click(function (e) {
        e.preventDefault();
        var input = '<input name="return_to_view" type="hidden" value="1"/>';
        $("#redirect_to_view_div").html(input);
        setTimeout(function () {
            $("#demo-form2").submit();
        }, 500);
    });

    $(".active_reward").click(function (e) {
        e.preventDefault();
        var input = '<input name="active_reward" type="hidden" value="1"/>';
        $("#active_reward").html(input);
        setTimeout(function () {
            $("form").submit();
        }, 500);
    });

    $(".renewals-leads-data-table").DataTable({
        ordering: false,
        info: true,
        searching: false,
        bLengthChange: false,
        processing: true,
        stateSave: true,
        paging: true,
        ajax: config.routes.renewals_leads_datatable_route,
        columns: [
            { data: "id", name: "id" },
            { data: "renewal_import_type", name: "renewal_import_type" },
            { data: "renewal_import_code", name: "renewal_import_code" },
            { data: "file_name", name: "file_name" },
            { data: "total_records", name: "total_records" },
            { data: "good", name: "good" },
            { data: "cannot_upload", name: "cannot_upload" },
            { data: "status", name: "status" },
            { data: "uploaded_by", name: "uploaded_by" },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },
        ],
    });

    var searchLeadsTable = $(".leadSearch-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: {
            url: config.routes.searchLeadsDataTable,
            data: function (d) {
                d.leadType = $("#leadType").val();
                d.cdbID = $("#cdbID").val();
                d.email = $("#email").val();
                d.phnNumber = $("#phnNumber").val();
            },
        },
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                if (row.access) {
                    return "<a href='" + config.routes.searchLeadsDataTable + '/' + row.id + "'>" + row.id + "</a>"
                } else {
                    return row.id
                }
            }
        },


        { data: "created_at", name: "created_at" },
        { data: "first_name", name: "first_name" },
        { data: "last_name", name: "last_name" },
        { data: "advisor_name", name: "advisor_name" },
        ],
    });
    $("#search-leads").submit(function (e) {
        e.preventDefault();
        $(".loader").show();
        searchLeadsTable.draw();
        setTimeout(() => {
            $(".loader").hide();
        }, 1000);

    });
    $('#car_make_value').on('change', function (e) {
        var make_code = $("#car_make_value option:selected").attr('data-id');
        $.get('/valuation/car-models?make_code=' + make_code, function (data) {
            var carmodel = $('#car_model_value').empty();
            carmodel.append('<option value="">Select</option>');
            $.each(data, function (create, carmodelObj) {
                var option = $('<option/>', { id: create, value: carmodelObj });
                carmodel.append('<option value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
            });
        });
    });
    $('#car_model_value').on('change', function (e) {
        var modelId = $("#car_model_value option:selected").val();
        $.get('/valuation/car-model-detail?modelId=' + modelId, function (data) {
            var cartrim = $('#car_trim_value').empty();
            cartrim.append('<option value="">Select</option>');
            if (data.length == 0) {
                cartrim.append('<option value="">No Trim Available</option>');
                $('#car_trim_value option:eq(1)').prop('selected', true);
            }
            $.each(data, function (create, cartrimObj) {
                var option = $('<option/>', { id: create, value: cartrimObj });
                cartrim.append('<option value="' + cartrimObj.id + '">' + cartrimObj.text + '</option>');
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
    var corpLineDataTable = $(".corpline-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: {
            url: config.routes.amtDataTable,
            data: function (d) {
                d.leadType = $("#leadStatus").val();
                d.cdbID = $("#cdbID").val();
            },
        },
        columns: [{
            data: 'code',
            name: 'code',
            render: function (data, type, row) {
                var href = '/quotes/business/' + row.uuid;
                return "<a href='" + href + "'>" + row.code + "</a>";
            }
        },
        { data: "first_name", name: "first_name" },
        { data: "last_name", name: "last_name" },
        { data: "leadStatus", name: "leadStatus" },
        { data: "leadType", name: "leadType" },
        { data: "created_at", name: "created_at" },
        { data: "updated_at", name: "updated_at" },

        ],
    });


    $('#manualAssignBtn').on('click', function (e) {
        e.preventDefault();
        if ($('#assigned_to_id_new').val() == '') {
            $('#userAssignValidation').show().fadeOut(5000);
        } else {
            $.ajax({
                url: '/leadassignment/manualLeadAssign',
                type: "PUT",
                data: { selectTmLeadId: $('#entityId').val(), assigned_to_id_new: $('#assigned_to_id_new').val(), _token: config._token },
                success: function (response) {
                    $("#teamassignmentSuccess").html('Team Assigned Successfully').show().fadeOut(5000);
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
        var txt = $(this).find("option:selected").data('id');
        if (txt) {
            txt = txt.replace(/(\r\n|\n|\r)/gm, "");
            $('#tooltipGm').show();
            $('#tooltipGm').attr('title', txt);
        } else {
            $('#tooltipGm').hide();
        }
    });
    $('#lob_team').select2({
        placeholder: "Select LOB For Duplication",
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
        columns: [{
            data: 'id',
            name: 'id',
            render: function (data, type, row) {
                return "<a href='" + config.routes.vehiclevalue_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
            }
        },
        { data: 'car_make_text', name: 'car_make_text' },
        { data: 'car_model_text', name: 'car_model_text' },
        { data: 'car_trim_text', name: 'car_trim_text' },
        { data: 'ip_text', name: 'ip_text' },
        { data: 'current_value', name: 'current_value' },
        ]
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
            url: '/quotes/getLeadHistory?modelType=' + modelType + '&recordId=' + leadId,
            type: "GET",
            success: function (response) {
                var html = '';
                if (response.length > 0) {
                    for (let i = 0; i < response.length; i++) {
                        const element = response[i];
                        element.ModifiedAt = element.ModifiedAt == null ? '' : element.ModifiedAt;
                        element.ModifiedBy = element.ModifiedBy == null ? '' : element.ModifiedBy;
                        element.NewStatus = element.NewStatus == null ? '' : element.NewStatus;
                        element.NewAdvisor = element.NewAdvisor == null ? '' : element.NewAdvisor;
                        element.NewNotes = element.NewNotes == null ? '' : element.NewNotes;

                        html = html + '<tr><td>' + element.ModifiedAt + '</td><td>' + element.ModifiedBy + '</td><td>' + element.NewStatus + '</td><td>' + element.NewAdvisor + '</td><td>' + element.NewNotes + '</td></tr>';
                    }
                } else {
                    html = '<tr><td colspan="5" style="text-align: center">No data available</td></tr>';
                }
                $('#leadhistorydatatable tbody').html(html);
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
            method: "POST",
            data: {
                activity_id: $(this).val(),
                _token: $('input[name=_token]').val()
            },
            success: function (data) {
                disabledDoneActivities();
            },
        });
    });

    $(".renewals-batches-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        stateSave: true,
        paging: true,
        processing: true,
        ajax: config.routes.renewals_batches_datatable_route,
        columns: [{
            data: 'renewal_batch',
            name: 'renewal_batch',
            render: function (data, type, row) {
                return "<a href='" + config.routes.renewals_batches_datatable_route + '/' + row.renewal_batch + "'>" + row.renewal_batch + "</a>"
            }
        },
        ],
    });

});


$("#renewals-upload-button").click(function () {
    $("#renewals-upload-button").hide();
    $("#renewals-upload-button-text").text("Please wait until file will be uploaded. More waiting time is depending on number of records.");
});

$('#insurance_provider_id').on('change', function (e) {
    var insuranceProviderId = $("#insurance_provider_id option:selected").val();
    var quoteUuId = $("#car_quote_uuid").val();
    $.get('/insurance-provider-plans?insuranceProviderId=' + insuranceProviderId + '&quoteUuId=' + quoteUuId, function (data) {
        var carPlan = $('#car_plan_id').empty();
        $('#car_plan_id').animate({ borderColor: '#007bff' }).delay(5);
        $('#car_plan_id').delay(100).fadeOut().fadeIn('slow');
        $('#car_plan_id').animate({ borderColor: '#ced4da' }).delay(5);

        $.each(data, function (create, carPlanObj) {
            carPlan.append('<option value="' + carPlanObj.id + '">' + carPlanObj.text + ' (' + carPlanObj.repair_type + ')</option>');
        });
        if (data.length == 0) {
            carPlan.append('<option value="">Select Plan</option>');
        }
    });
});

$("#update_car_plans").submit(function (e) {
    var insurance_provider_id = $("#insurance_provider_id").val();
    var car_plan_id = $("#car_plan_id").val();
    var premium = $("#premium").val();
    var value = $("#value").val();
    var excess = $("#excess").val();

    if (insurance_provider_id == "" || (car_plan_id == "" || premium == "" || value == "" || excess == "")) {
        alert('Please fill all the fields');
        return false;
    }

});

$('#quotePlanModal').on('hidden.bs.modal', function () {
    location.reload();
});

function activityEdit1(el) {
    var id = $(el).attr('data-record-id');
    var type = $(el).attr('data-type');
    var quote_uuid = $(el).attr('data-quote-uuid');
    $.ajax({
        url: '/activities/getEditView',
        method: "POST",
        data: {
            activity_id: id,
            quoteType: type,
            quote_uuid: quote_uuid,
            _token: $('input[name=_token]').val()
        },
        success: function (data) {
            $('#activityEditModalContent').html(data);
            $('#activityEditModalContent > form').append('<input type="hidden" name="fromLeadView" value="1">');
            $('#activityEditModal').modal('show');
        },
    });
}

function isActivityFormValid() {
    var isValid = true;
    if ($('#title').val() == '') {
        $('#title').next('span').html('Title is required').delay(5000).hide(0);;
        isValid = false;
    }
    if ($('#description').val() == '') {
        $('#description').next('span').html('Description is required').delay(5000).hide(0);;
        isValid = false;
    }
    if ($('#due_date').val() == '') {
        $('#due_date').next('span').html('Due Date is required').delay(5000).hide(0);;
        isValid = false;
    }
    if ($('#assignee_id').val() == '') {
        $('#assignee_id').next('span').html('Assignee is required').delay(5000).hide(0);;
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
            method: "POST",
            data: {
                title: $('#title').val(),
                description: $('#description').val(),
                due_date: $('#due_date').val(),
                assignee_id: $('#assignee_id').val(),
                _token: $('input[name=_token]').val()
            },
            success: function (data) {
                $('#activityEditModal').modal('hide');
                $('#sucess-div').text('Activity updated successfully').show().delay(5000).hide(0);
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
            method: "POST",
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
    }
    else {
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

$('#send-note-for-customer-btn').on('click', function(){
    $('#notesForCustomerModal').modal({ show: true });
});

$("#quote_policy_issuance_date, #quote_policy_start_date, #quote_policy_expiry_date").datepicker({
    changeMonth: true,
    changeYear: true,
    dateFormat: "dd-mm-yy"
});

// allow only numbers and decimal, ref html: onkeypress="return isNumberKey(event,this)"
function isNumberKey(evt, obj) {
    var charCode = (evt.which) ? evt.which : event.keyCode
    var value = obj.value;
    var dotcontains = value.indexOf(".") != -1;
    if (dotcontains)
        if (charCode == 46) return false;
    if (charCode == 46) return true;
    if (charCode > 31 && (charCode < 48 || charCode > 57))
        return false;
    return true;
}