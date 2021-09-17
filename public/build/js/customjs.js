$(document).ready(function() {
    $(".selectpicker").selectpicker();
    $("#datepicker").datepicker({ dateFormat: "yy-mm-dd" });
    $("#datepicker_2").datepicker({ dateFormat: "yy-mm-dd" });
    $("#transapp_start_date").datepicker({ dateFormat: "yy-mm-dd" });
    $("#transapp_stop_date").datepicker({ dateFormat: "yy-mm-dd" });

    $("#enquiry_date, #allocation_date, #tmLeadsStartDate, #tmLeadsEndDate, #dob").datepicker({ // TM Leads
        changeMonth: true,changeYear: true,dateFormat: "yy-mm-dd",yearRange: "-20:+00"});

    $("#next_followup_date").daterangepicker({ // TM Leads
        timePicker: true,
        singleDatePicker: true,
        timePicker24Hour: true,
        minDate: new Date(),
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
        errorPlacement: function(error, element) {
            error.addClass('invalid-feedback').attr('style', 'font-size: 17px');
            element.closest('.form-group').append(error);
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
        }
    });
    $('#calculateValuation').click(function() {
        if ($('#search-valuation').valid()) {
            var carMake = $('#car_make_value option:selected').val();
            var carModel = $('#car_model_value option:selected').val();
            var carTrim = $('#car_trim_value option:selected').val();
            var yom = $('#yom').val();
            $.ajax({
                url: config.routes.valuation_api_route + 'get-vehicle-value',
                type: "post",
                data: { carModelDetailId: carTrim, yearOfManufacture: yom },
                success: function(response) {
                    $('#carValue').text(Number(response.carValue).toFixed(2));
                    $('#uLimit').text(Number(response.carValueUpperLimit).toFixed(2));
                    $('#lLimit').text(Number(response.carValueLowerLimit).toFixed(2));
                    $('#result').show();
                },
                error: function(jqXHR, textStatus, errorThrown) {
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
    $('#reset').click(function() {
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
        // imageUpload: true, // Activate the option
        // uploadPath: 'upload.php',
        onPreview: function(content, callback) {
            callback(marked(content));
        },
    });

    $("#editor2").markdownEditor({
        preview: true,
        fullscreen: false,
        // imageUpload: true, // Activate the option
        // uploadPath: 'upload.php',
        onPreview: function(content, callback) {
            callback(marked(content));
        },
    });

    $("#editor3").markdownEditor({
        preview: true,
        fullscreen: false,
        // imageUpload: true, // Activate the option
        // uploadPath: 'upload.php',
        onPreview: function(content, callback) {
            callback(marked(content));
        },
    });

    $("#editor4").markdownEditor({
        preview: true,
        fullscreen: false,
        // imageUpload: true, // Activate the option
        // uploadPath: 'upload.php',
        onPreview: function(content, callback) {
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
                render: function(data, type, row) {
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
                render: function(data, type, row, meta) {
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
                render: function(data, type, row) {
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
                render: function(data, type, row) {
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
                render: function(data, type, row) {
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
            data: function(d) {
                d.email = $("#users_email").val();
                d.name = $("#users_name").val();
            }
        },
        columns: [{
                data: "id",
                name: "id",
                render: function(data, type, row) {
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
                render: function(data, type, row) {
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
                render: function(data, type, row) {
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
                render: function(data, type, row) {
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
                render: function(data, type, row) {
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
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: {
            url: config.routes.transaction_datatable_route,
            data: function(d) {
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
            //{ data: "type_of_insurance", name: "type_of_insurance" },
            { data: "created_by_name", name: "created_by_name" },
            { data: "handler_name", name: "handler_name" },
            { data: "payment_mode", name: "payment_mode" },
            { data: "prev_approval_code", name: "prev_approval_code" },
        ]
    });

    $("#search-transactions").submit(function(e) {
        e.preventDefault();
        $(".loader").show();
        transactionsDatatable.draw();
        setTimeout(() => {
            $(".loader").hide();
        }, 1000);
    });

    $("#search-users").submit(function(e) {
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
                render: function(data, type, row) {
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
                render: function(data, type, row) {
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
            data: function(d) {
                d.searchtype = $("#search_type").val();
                d.searchfield = $("input[name=searchfield]").val();
                d.quotestatus = $("#quote_status_value").val();
                d.paymentstatus = $("#payment_status_value").val();
            },
        },
        columns: [{
                data: 'id',
                name: 'id',
                render: function(data, type, row) {
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

    $("#search-car-quote").submit(function(e) {
        e.preventDefault();
        $(".loader").show();
        carquoteDatatable.draw();
        setTimeout(() => {
            $(".loader").hide();
        }, 1000);
    });

    $("#resubmit_api_carquote").click(function() {
        $(".loader").show();
        $("#resubmit_api_carquote").attr("disabled", true);
        var data_id = $(this).attr("data-id");
        var carQuotes = [];
        if (data_id != "") carQuotes.push(data_id);
        $(".multicheckbox").each(function(index) {
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
                success: function(response) {
                    $(".loader").hide();
                    $("#success_message").show();
                    $("#success_message")
                        .fadeIn()
                        .html("Resubmit Api Execution is successfull");

                    $(".carquote-data-table").DataTable().ajax.reload();

                    setTimeout(function() {
                        $("#resubmit_api_carquote").attr("disabled", false);
                        $("#success_message").fadeOut("slow");
                    }, 3000);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $(".loader").hide();
                    $("#error_message").show();
                    $("#error_message").fadeIn().html(errorThrown);
                    setTimeout(function() {
                        $("#resubmit_api_carquote").attr("disabled", false);
                        $("#error_message").fadeOut("slow");
                    }, 3000);
                },
            });
        } else {
            $(".loader").hide();
            $("#error_message").show();
            $("#error_message").fadeIn().html("Please select atleast one row");
            setTimeout(function() {
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
                render: function(data, type, row) {
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
            data: function(d) {
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
                render: function(data, type, row) {
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

    $("#search-claims").submit(function(e) {
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
                render: function(data, type, row) {
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
                render: function(data, type, row) {
                    return "<a href='" + config.routes.vehicledepreciation_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
                }
            },
            { data: 'car_make_text', name: 'car_make_text' },
            { data: 'car_model_text', name: 'car_model_text' },
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
            { data: 'upper_limit', name: 'first_year' },
            { data: 'lower_limit', name: 'first_year' },
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
                render: function(data, type, row) {
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
                render: function(data, type, row) {
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
                render: function(data, type, row) {
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
                render: function(data, type, row) {
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

    $('#car_make_id').on('change', function(e) {
        var make_code = $("#car_make_id option:selected").attr('data-id');
        $.get('/car-model?make_code=' + make_code, function(data) {
            var carmodel = $('#car_model_id').empty();
            $.each(data, function(create, carmodelObj) {
                var option = $('<option/>', { id: create, value: carmodelObj });
                carmodel.append('<option data-id="' + carmodelObj.code + '" value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
            });
        });
    });

    // Claims-EditView: Populate models of selected car make
    var make_code = $("#car_make_id option:selected").attr('data-id');
    var old_car_model_id = $("#old_car_model_id").val();
    $.get('/car-model?make_code=' + make_code, function(data) {
        var carmodel = $('#car_model_id').empty();
        $.each(data, function(create, carmodelObj) {
            var option = $('<option/>', { id: create, value: carmodelObj });
            if (old_car_model_id == carmodelObj.id)
                carmodel.append('<option selected data-id="' + carmodelObj.code + '" value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
            else
                carmodel.append('<option  data-id="' + carmodelObj.code + '" value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
        });
    });

    $("#sub_type_of_insurance").hide();
    $("#car_fields").hide();
    type_of_insurance_fields_visibility();
    $('#type_of_insurances_id').on('change', function(e) {
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
            data: function(d) {
                d.searchtype = $("#search_type").val();
                d.searchfield = $("#searchfield").val();
            },
        },
        columns: [{
                data: 'id',
                name: 'id',
                render: function(data, type, row) {
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

    $("#search-customer").submit(function(e) {
        e.preventDefault();
        $(".loader").show();
        customerDataTable.draw();
        setTimeout(() => {
            $(".loader").hide();
        }, 1000);
    });

    var amlDatatable = $('.aml-data-table').DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: {
            url: config.routes.aml_datatable_route,
            data: function(d) {
                d.searchType = $("#searchType").val();
                d.searchField = $("input[name=searchField]").val();
                d.quoteType = $("#quoteTypeValue").val();
            },
        },
        columns: [{
                data: 'id',
                name: 'id',
                render: function(data, type, row) {
                    return "<a href='" + config.routes.aml_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
                }
            },
            { data: "quote_type_text", name: "quote_type_text" },
            {
                data: 'quote_request_id',
                name: 'quote_request_id',
                render: function(data, type, row) {
                    return "<a href='" + config.routes.aml_datatable_route + '/' + row.quote_type_id + '/details/' + row.quote_request_id + "'>" + row.quote_request_id + "</a>"
                }
            },
            { data: "input", name: "input" },
            {
                data: "screenshot",
                name: "screenshot",
                render: function(data, type, row, meta) {
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

    $("#searchAML").submit(function(e) {
        e.preventDefault();
        $(".loader").show();
        amlDatatable.draw();
        setTimeout(() => {
            $(".loader").hide();
        }, 1000);
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
                render: function(data, type, row) {
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
                render: function(data, type, row) {
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
                render: function(data, type, row) {
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

    var isCurrentUserIsAdvisor = $("#isCurrentUserIsAdvisor").val();
    if (isCurrentUserIsAdvisor == 0) {
        var Bfrtip = 'Bfrtip';
    } else {
        var Bfrtip = '';
    }

    var tmLeadsDatatable = $('.tmlead-data-table').DataTable({
        dom: Bfrtip,
        "buttons": [{
            "extend": 'csv',
            "text": '<i class="fa fa-download" style="color:orange;" id="tm-leads-export"></i>',
            "titleAttr": 'Download CSV',
            "action": newexportaction
        }],
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: {
            url: config.routes.tmlead_datatable_route,
            data: function(d) {
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
                render: function(data, type, row, meta) {
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
                render: function(data, type, row) {
                    return "<a href='" + config.routes.tmlead_datatable_route + '/' + row.id + "'>" + row.cdb_id + "</a>"
                }
            },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'tm_insurance_types_text', name: 'tm_insurance_types_text' },
            { data: 'tm_lead_status_text', name: 'tm_lead_status_text' },
            { data: 'notes', name: 'notes' },
            { data: 'enquiry_date', name: 'enquiry_date' },
            { data: 'allocation_date', name: 'allocation_date' },
            { data: 'next_followup_date', name: 'next_followup_date' },
            { data: 'handlers_name', name: 'handlers_name' },
            { data: 'created_at', name: 'created_at' },
            { data: 'updated_at', name: 'updated_at' },
        ],
        createdRow: function(row, data, index) {

            if (data.tm_lead_status_code == "NoAnswer" || data.tm_lead_status_code == "SwitchedOff" ||
                data.tm_lead_status_code == "PipelineNoInfo" || data.tm_lead_status_code == "PipelineImmediate" ||
                data.tm_lead_status_code == "PipelineFuture" || data.tm_lead_status_code == "DealingWithAnAdvisor") {

                var d = new Date();
                var currentTimestmap = d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2) +
                    " " + ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2) + ":" + ("0" + d.getSeconds()).slice(-2);

                if (currentTimestmap > data.next_followup_date) {
                    $('td', row).eq(8).css('color', 'red');
                }

                console.log("currentTimestmap: " + currentTimestmap);
                console.log('tm_lead_id: ' + data.id);
                console.log('next_followup_date: ' + data.next_followup_date);
            }
        },
        drawCallback: function(settings) {
            var api = new $.fn.dataTable.Api(settings);
            console.log( "TotalTmLeadsss: ",api.rows().data().length );
            $("#totalLeads").text("Total Leads: "+api.rows().data().length);
        }
    });

    // TM Leads: Expost data into csv
    function newexportaction(e, dt, button, config) {
        var self = this;
        var oldStart = dt.settings()[0]._iDisplayStart;
        dt.one('preXhr', function(e, s, data) {
            data.start = 0;
            data.length = 2147483647;
            dt.one('preDraw', function(e, settings) {
                if (button[0].className.indexOf('buttons-csv') >= 0) {

                    $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                        $.fn.dataTable.ext.buttons.csvHtml5.action.call(self, e, dt, button, config) :
                        $.fn.dataTable.ext.buttons.csvFlash.action.call(self, e, dt, button, config);
                }
                dt.one('preXhr', function(e, s, data) {
                    settings._iDisplayStart = oldStart;
                    data.start = oldStart;
                });
                setTimeout(dt.ajax.reload, 0);
                return false;
            });
        });
        dt.ajax.reload();
    };

    // TM Leads: Search button trigger
    $("#tm-leads-export").hide();
    $("#search-tm-leads").submit(function(e) {
        e.preventDefault();
        $(".loader").show();

        var tmLeadsStartDate = $("#tmLeadsStartDate").val();
        var tmLeadsEndDate = $("#tmLeadsEndDate").val();
        var searchType = $("#searchType").val();

        if (searchType == "createdAt" || searchType == "updatedAt" || searchType == "nextFollowupDate" ||
            searchType == "enquiryDate" || searchType == "allocationDate") {

            var date1 = new Date(tmLeadsStartDate);
            var date2 = new Date(tmLeadsEndDate);

            var time_difference = date2.getTime() - date1.getTime();
            var daysDiff = time_difference / (1000 * 60 * 60 * 24);

            if ((tmLeadsStartDate == "") || (tmLeadsEndDate == "")) {
                $("#result").html("Please select start & end dates");
                $('#tmLeadsStartDate').css('border-color', 'red');
                $('#tmLeadsEndDate').css('border-color', 'red');
                $("#tm-leads-export").hide();

                setTimeout(() => {
                    $(".loader").hide();
                }, 1000);
                return false
            } else if (tmLeadsStartDate > tmLeadsEndDate) {
                $("#result").html("Start date must be equal or less than end date");
                $('#tmLeadsStartDate').css('border-color', 'red');
                $('#tmLeadsEndDate').css('border-color', 'red');
                $("#tm-leads-export").hide();

                setTimeout(() => {
                    $(".loader").hide();
                }, 1000);
                return false
            } else if (daysDiff > 30) {
                $("#result").html("Allowed number of days between start and and dates are 30 days.");
                $('#tmLeadsStartDate').css('border-color', 'red');
                $('#tmLeadsEndDate').css('border-color', 'red');
                $("#tm-leads-export").hide();

                setTimeout(() => {
                    $(".loader").hide();
                }, 1000);
                return false
            } else {
                tmLeadsDatatable.draw();
                $("#result").html("");
                $('#tmLeadsStartDate').css('border-color', '');
                $('#tmLeadsEndDate').css('border-color', '');
                $("#tm-leads-export").show();
                setTimeout(() => {
                    $(".loader").hide();
                }, 1000);
            }
        } else {
            tmLeadsDatatable.draw();
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
    $("#checkAllTmLeads").click(function() {
        $('input:checkbox').not(this).prop('checked', this.checked);

        var idsArray = $('#selectTmLeadId').val();
        $('input:checkbox').each(function(i, item) {
            idsArray = idsArray + $(item).val() + ',';
        });
        $('#selectTmLeadId').val(idsArray.replace(/^,|,$/g, ''));
    });

    // TM Leads: Select tm leads id and store in hidden field
    $("#tmLeadsAssignToUser").click(function() {
        var tmLeadIDs = [];
        $.each($("input[name='tmLeadID']:checked"), function() {
            tmLeadIDs.push($(this).val());
        });
        $('#selectTmLeadId').val(tmLeadIDs);
        console.log("tmLeadIDs: " + tmLeadIDs);
    });

    // TM: Selecting a single record should also enable manual allocation
    $(document).on("change", "#tmLeadID", function() {
        var countSelectedTmLeadIds = document.querySelectorAll('#tmLeadID:checked').length;
        console.log(countSelectedTmLeadIds);
        if(countSelectedTmLeadIds > 0) {
            $("#tm-leads-assign-div").show(300);
        }
        else {
            $('#checkAllTmLeads').prop('checked', false);
            $("#tm-leads-assign-div").hide(300);
        }
    });

    // TM Leads: On check main checkbox, display lead assignment panel
    $("#tm-leads-assign-div").hide();
    $("#checkAllTmLeads").click(function() {
        if ($(this).is(":checked")) {
            $("#tm-leads-assign-div").show(300);
        } else {
            $("#tm-leads-assign-div").hide(200);
        }
    });

    // TM Leads: OnClick on phone number ignore redirection
    $("#ignore-redirection").click(function() {
        return false;
    });

    // TM Leads: Display Car fields if insurance type Car is selected
    $("#tm_car_fields").hide();
    $("#tm_dob_field").hide();
    tm_type_of_insurance_fields_visibility();
    $('#tm_insurance_types_id').on('change', function(e) {
        tm_type_of_insurance_fields_visibility();
    });

    function tm_type_of_insurance_fields_visibility() {
        var tm_insurance_types_id_code = $("#tm_insurance_types_id option:selected").attr('data-id');

        if (tm_insurance_types_id_code == 'Car') {
            $("#tm_car_fields").show();
        } else {
            $("#tm_car_fields").hide();
        }

        if (tm_insurance_types_id_code == 'Car' || tm_insurance_types_id_code == 'Bike'
        || tm_insurance_types_id_code == 'Life' || tm_insurance_types_id_code == 'Health') {
            $("#tm_dob_field").show();
        } else {
            $("#tm_dob_field").hide();
        }
    }

    // TM Leads: Display Followup date conditionally
    $("#next_followup_date_field").hide();
    next_followup_date_field_visibility();
    $('#tm_lead_statuses_id').on("change", function(e) {
        next_followup_date_field_visibility();
    });

    function next_followup_date_field_visibility() {
        var tm_lead_status_code = $("#tm_lead_statuses_id option:selected").attr("data-id");
        var no_answer_count = $("#no_answer_count").val();

        if (((tm_lead_status_code == "NoAnswer" || tm_lead_status_code == "SwitchedOff") &&
                (typeof no_answer_count === "undefined" || no_answer_count < "3")) ||
            (tm_lead_status_code == "PipelineNoInfo" || tm_lead_status_code == "PipelineImmediate" ||
                tm_lead_status_code == "PipelineFuture" || tm_lead_status_code == "DealingWithAnAdvisor")) {
            $("#next_followup_date_field").show();
        } else {
            $("#next_followup_date_field").hide();
        }
    }

    // TM Leads: Display date fields and search value field conditionally
    $("#tmLeads-search-value-filter").show();
    $("#tmLeads-search-start-end-dates-filters").hide();
    $('#searchType').on("change", function(e) {
        tmleads_search_start_end_dates_filters_visiblity();
    });

    function tmleads_search_start_end_dates_filters_visiblity() {
        var searchTypeValue = $("#searchType").val();
        console.log("searchTypeValue: " + searchTypeValue);
        if (searchTypeValue == "createdAt" || searchTypeValue == "updatedAt" || searchTypeValue == "nextFollowupDate" ||
            searchTypeValue == "enquiryDate" || searchTypeValue == "allocationDate") {
            $("#tmLeads-search-start-end-dates-filters").show(300);
            $("#tmLeads-search-value-filter").hide(300);
        } else {
            $("#tmLeads-search-start-end-dates-filters").hide(300);
            $("#tmLeads-search-value-filter").show(300);
        }
    }

    $('.tmuploadlead-data-table').DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.tmuploadlead_datatable_route,
        columns: [{
                data: 'id',
                name: 'id',
                render: function(data, type, row) {
                    return "<a href='" + config.routes.tmuploadlead_datatable_route + '/' + row.id + "'>" + row.id + "</a>"
                }
            },
            { data: 'file_name', name: 'file_name' },
            { data: 'total_records', name: 'total_records' },
            { data: 'good', name: 'good' },
            { data: 'cannot_upload', name: 'cannot_upload' },
            { data: 'user_name', name: 'user_name' },
            { data: 'created_at', name: 'created_at' },
        ]
    });

    //select/unselect all checkboxes if this selected
    $("#select_all_checkboxes").click(function(e) {
        var isChecked = e.target.checked;

        if (isChecked === true) {
            $(".multicheckbox").each(function(index) {
                $(this).prop("checked", true);
            });
        } else {
            $(".multicheckbox").each(function(index) {
                $(this).prop("checked", false);
            });
        }
    });

    $(".auditablebtn").click(function() {
        var auditableId = $(this).attr("data-id");
        var auditableType = $(this).attr("data-model");
        $(this).attr("disabled", true);

        $.ajax({
            url: config.routes.load_auditable,
            method: "POST",
            data: { auditableId, auditableType, _token: config._token },
            success: function(data) {
                $("#auditable").html(data);
                $(".auditablebtn").hide();
            },
        });
    });

    $("#return_to_view").click(function(e) {
        e.preventDefault();
        var input = '<input name="return_to_view" type="hidden" value="1"/>';
        $("#redirect_to_view_div").html(input);
        setTimeout(function() {
            $("form").submit();
        }, 500);
    });

    $(".active_reward").click(function(e) {
        e.preventDefault();
        var input = '<input name="active_reward" type="hidden" value="1"/>';
        $("#active_reward").html(input);
        setTimeout(function() {
            $("form").submit();
        }, 500);
    });
});
$('#car_make_value').on('change', function(e) {
    var make_code = $("#car_make_value option:selected").attr('data-id');
    $.get('/valuation/car-models?make_code=' + make_code, function(data) {
        var carmodel = $('#car_model_value').empty();
        carmodel.append('<option value="">Select</option>');
        $.each(data, function(create, carmodelObj) {
            var option = $('<option/>', { id: create, value: carmodelObj });
            carmodel.append('<option value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
        });
    });
});
$('#car_model_value').on('change', function(e) {
    var modelId = $("#car_model_value option:selected").val();
    $.get('/valuation/car-model-detail?modelId=' + modelId, function(data) {
        var cartrim = $('#car_trim_value').empty();
        cartrim.append('<option value="">Select</option>');
        if (data.length == 0) {
            cartrim.append('<option value="">No Trim Available</option>');
            $('#car_trim_value option:eq(1)').prop('selected', true);
        }
        $.each(data, function(create, cartrimObj) {
            var option = $('<option/>', { id: create, value: cartrimObj });
            cartrim.append('<option value="' + cartrimObj.id + '">' + cartrimObj.text + '</option>');
        });
    });
});

$(document).on('click', '.delete', function() {
    var route = $(this).attr('date-route');
    $('#delete-form').attr('action', route);
    $('#exampleModal').modal('show');

});

function dateRangePickerChange(startDate, endDate) {
    $.ajax({
        url: config.routes.load_dashboard_stats,
        method: "POST",
        data: { startDate, endDate, _token: config._token },
        success: function(data) {
            console.log("request " + data);
            $(".customer-count").html(data.totalCustomers);
            $(".carquote-count").html(data.totalCarQuotes);
            $(".duration").html(startDate + " - " + endDate);
            $(".loader").hide();
        },
    });
}
