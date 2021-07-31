$(document).ready(function () {
    $(".selectpicker").selectpicker();
    $("#datepicker").datepicker({ dateFormat: "yy-mm-dd" });
    $("#datepicker_2").datepicker({ dateFormat: "yy-mm-dd" });
    $("#transapp_start_date").datepicker({ dateFormat: "yy-mm-dd" });
    $("#transapp_stop_date").datepicker({ dateFormat: "yy-mm-dd" });
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
    $('#calculateValuation').click(function(){
        if($('#search-valuation').valid()){
            console.log('c');
            var carMake = $('#car_make_value option:selected').val();
            var carModel = $('#car_model_value option:selected').val();
            var carTrim = $('#car_trim_value option:selected').val();
            var yom = $('#yom').val();
            $.ajax({
                url: config.routes.valuation_api_route + 'get-vehicle-value',
                type: "post",
                data: { carModelDetailId: carTrim, yearOfManufacture: yom },
                success: function (response) {
                    console.log('c');
                    $('#carValue').text(response.carValue.toString());
                    $('#uLimit').text(response.carValueUpperLimit.toString());
                    $('#lLimit').text(response.carValueLowerLimit.toString());
                    $('#result').show();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if(jqXHR.responseJSON.msg == 'Car Trim Not found'){ 
                        $('#error').show();
                        $('#error').text('Cannot calculate depreciation without trim');
                        $('#error').hide().delay(5000).fadeIn(400);
                     }
                     else{
                        $('#error').show();
                        $('#error').text(jqXHR.responseJSON.msg);
                        $('#error').hide().delay(5000).fadeIn(400);
                    }
                },
            });
        }
    });
    $('#reset').click(function(){
        console.log('r');
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
        onPreview: function (content, callback) {
            callback(marked(content));
        },
    });

    $("#editor2").markdownEditor({
        preview: true,
        fullscreen: false,
        // imageUpload: true, // Activate the option

        // uploadPath: 'upload.php',
        onPreview: function (content, callback) {
            callback(marked(content));
        },
    });

    $("#editor3").markdownEditor({
        preview: true,
        fullscreen: false,
        // imageUpload: true, // Activate the option

        // uploadPath: 'upload.php',
        onPreview: function (content, callback) {
            callback(marked(content));
        },
    });

    $("#editor4").markdownEditor({
        preview: true,
        fullscreen: false,
        // imageUpload: true, // Activate the option

        // uploadPath: 'upload.php',
        onPreview: function (content, callback) {
            callback(marked(content));
        },
    });
    $("#datatable").DataTable().destroy();
    $("#datatable").DataTable({
        // "paging":   false,
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
        columns: [
            {
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
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
        ],
    });

    var rewardCategory = $(".reward-category-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        ajax: config.routes.reward_categories_datatable_route,
        columns: [
            {
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
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
        ],
    });

    var rewardTags = $(".reward-tag-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        ajax: config.routes.reward_tags_datatable_route,
        columns: [
            {
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
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
        ],
    });

    $(".reward-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        ajax: config.routes.reward_datatable_route,
        columns: [
            {
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
            // { data: "is_flat_discount", name: "is_flat_discount" },
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
        ],
    });

    $(".user-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.user_datatable_route,
        columns: [
            {
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
            { data: 'created_at', name: 'created_at' },
            { data: 'updated_at', name: 'updated_at' },
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
        ],
    });

    $(".role-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.role_datatable_route,
        columns: [
            {
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
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
        ],
    });

    $(".insurancecompany-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.insurancecompany_datatable_route,
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.insurancecompany_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            { data: "name", name: "name" },
            { data: "is_active", name: "is_active" },
            { data: "created_by", name: "created_by" },
            { data: "updated_by", name: "updated_by" },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
        ],
    });

    $(".handler-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.handler_datatable_route,
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.handler_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            { data: "name", name: "name" },
            { data: "is_active", name: "is_active" },
            { data: "created_by", name: "created_by" },
            { data: "updated_by", name: "updated_by" },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
        ],
    });

    $(".status-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.status_datatable_route,
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.status_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            { data: "name", name: "name" },
            { data: "is_active", name: "is_active" },
            { data: "created_by", name: "created_by" },
            { data: "updated_by", name: "updated_by" },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
        ],
    });

    var transactionsDatatable = $('.transaction-data-table').DataTable({
        ordering: false,
        info:     false,
        searching:false,
        bLengthChange: false,
        serverSide: true,
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

    // $(".transaction-data-table").DataTable({
    //     ordering: false,
    //     info: false,
    //     searching: false,
    //     bLengthChange: false,
    //     serverSide: true,
    //     responsive: true,
    //     ajax: config.routes.transaction_datatable_route,
    //     columns: [
    //         { data: "approval_code", name: "approval_code" },
    //         { data: "created_at", name: "created_at" },
    //         { data: "insurance", name: "insurance" },
    //         { data: "amount_paid", name: "amount_paid" },
    //         { data: "customer_name", name: "customer_name" },
    //         { data: "risk_details", name: "risk_details" },
    //         { data: "created_by_name", name: "created_by_name" },
    //         { data: "handler_name", name: "handler_name" },
    //         { data: "payment_mode", name: "payment_mode" },
    //         { data: "prev_approval_code", name: "prev_approval_code" },
    //     ],
    // });

    $("#search-transactions").submit(function (e) {
        e.preventDefault();
        $(".loader").show();
        transactionsDatatable.draw();
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
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.reason_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            { data: "name", name: "name" },
            { data: "is_active", name: "is_active" },
            { data: "created_by", name: "created_by" },
            { data: "updated_by", name: "updated_by" },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
        ],
    });

    $(".paymentmode-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.paymentmode_datatable_route,
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.paymentmode_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            { data: "name", name: "name" },
            { data: "is_active", name: "is_active" },
            { data: "created_by", name: "created_by" },
            { data: "updated_by", name: "updated_by" },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
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
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.carquote_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            { data: "car_value", name: "car_value" },
            { data: "is_synced", name: "is_synced" },
            { data: "device", name: "device" },
            { data: "code", name: "code" },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
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
                    console.log(textStatus, errorThrown);
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
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.healthquote_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            { data: "preference", name: "preference" },
            { data: "is_synced", name: "is_synced" },
            { data: "device", name: "device" },
            { data: "code", name: "code" },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
        ],
    });

    var claimsDatatable = $('.claim-data-table').DataTable({
        ordering: false,
        info:     false,
        searching:false,
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
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.claim_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
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
        info:     false,
        searching:false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.typeofinsurance_datatable_route,
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.typeofinsurance_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            {data: 'text', name: 'text'},
            {data: 'text_ar', name: 'text_ar'},
            {data: 'sort_order', name: 'sort_order'},
            {data: 'is_active', name: 'is_active'},
            {data: 'created_at', name: 'created_at'},
            {data: 'updated_at', name: 'updated_at'},
        ]
    });

    $('.vehicledepreciation-data-table').DataTable({
        ordering: false,
        info:     false,
        searching:false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.vehicledepreciation_datatable_route,
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.vehicledepreciation_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            {data: 'car_make_text', name: 'car_make_text'},
            {data: 'car_model_text', name: 'car_model_text'},
            {data: 'first_year', name: 'first_year'},
            {data: 'second_year', name: 'first_year'},
            {data: 'third_year', name: 'first_year'},
            {data: 'fourth_year', name: 'first_year'},
            {data: 'fifth_year', name: 'first_year'},
            {data: 'sixth_year', name: 'first_year'},
            {data: 'seventh_year', name: 'first_year'},
            {data: 'eighth_year', name: 'first_year'},
            {data: 'ninth_year', name: 'first_year'},
            {data: 'tenth_year', name: 'first_year'},
            {data: 'upper_limit', name: 'first_year'},
            {data: 'lower_limit', name: 'first_year'},
        ]
    });

    $('.subtypeofinsurance-data-table').DataTable({
        ordering: false,
        info:     false,
        searching:false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.subtypeofinsurance_datatable_route,
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.subtypeofinsurance_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            {data: 'text', name: 'text'},
            {data: 'text_ar', name: 'text_ar'},
            {data: 'sort_order', name: 'sort_order'},
            {data: 'is_active', name: 'is_active'},
            {data: 'created_at', name: 'created_at'},
            {data: 'updated_at', name: 'updated_at'},
        ]
    });

    $('.claimsstatus-data-table').DataTable({
        ordering: false,
        info:     false,
        searching:false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.claimsstatus_datatable_route,
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.claimsstatus_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            {data: 'text', name: 'text'},
            {data: 'text_ar', name: 'text_ar'},
            {data: 'sort_order', name: 'sort_order'},
            {data: 'is_active', name: 'is_active'},
            {data: 'created_at', name: 'created_at'},
            {data: 'updated_at', name: 'updated_at'},
        ]
    });

    $('.carrepaircoverage-data-table').DataTable({
        ordering: false,
        info:     false,
        searching:false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.carrepaircoverage_datatable_route,
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.carrepaircoverage_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            {data: 'text', name: 'text'},
            {data: 'text_ar', name: 'text_ar'},
            {data: 'sort_order', name: 'sort_order'},
            {data: 'is_active', name: 'is_active'},
            {data: 'created_at', name: 'created_at'},
            {data: 'updated_at', name: 'updated_at'},
        ]
    });

    $('.carrepairtype-data-table').DataTable({
        ordering: false,
        info:     false,
        searching:false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.carrepairtype_datatable_route,
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.carrepairtype_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            {data: 'text', name: 'text'},
            {data: 'text_ar', name: 'text_ar'},
            {data: 'sort_order', name: 'sort_order'},
            {data: 'is_active', name: 'is_active'},
            {data: 'created_at', name: 'created_at'},
            {data: 'updated_at', name: 'updated_at'},
        ]
    });

    $('.rentacar-data-table').DataTable({
        ordering: false,
        info:     false,
        searching:false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.rentacar_datatable_route,
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.rentacar_datatable_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            {data: 'text', name: 'text'},
            {data: 'text_ar', name: 'text_ar'},
            {data: 'sort_order', name: 'sort_order'},
            {data: 'is_active', name: 'is_active'},
            {data: 'created_at', name: 'created_at'},
            {data: 'updated_at', name: 'updated_at'},
        ]
    });

    // Claims-CreateView: Populate models of selected car make
    $('#car_make_id').on('change',function(e) {
        var make_code = $("#car_make_id option:selected").attr('data-id');
        $.get('/car-model?make_code='+ make_code,function(data) {
            var carmodel = $('#car_model_id').empty();
            $.each(data,function(create,carmodelObj) {
                var option = $('<option/>', {id:create, value:carmodelObj});
                carmodel.append('<option data-id="'+carmodelObj.code+'" value="'+carmodelObj.id+'">'+carmodelObj.text+'</option>');
            });
        });
    });

    // Claims-EditView: Populate models of selected car make
    var make_code = $("#car_make_id option:selected").attr('data-id');
    var old_car_model_id = $("#old_car_model_id").val();
    $.get('/car-model?make_code='+ make_code,function(data) {
        var carmodel = $('#car_model_id').empty();
        $.each(data,function(create,carmodelObj) {
            var option = $('<option/>', {id:create, value:carmodelObj});
            if(old_car_model_id == carmodelObj.id)
                carmodel.append('<option selected data-id="'+carmodelObj.code+'" value="'+carmodelObj.id+'">'+carmodelObj.text+'</option>');
            else
                carmodel.append('<option  data-id="'+carmodelObj.code+'" value="'+carmodelObj.id+'">'+carmodelObj.text+'</option>');
        });
    });

    // Claims-CreateView: Fields visibility on the basis of selected Type of Insurance > Create/Edit views
    $("#sub_type_of_insurance").hide();
    $("#car_fields").hide();
    type_of_insurance_fields_visibility();
    $('#type_of_insurances_id').on('change',function(e) {
        type_of_insurance_fields_visibility();
    });
    function type_of_insurance_fields_visibility() {
        var type_of_insurance_text = $("#type_of_insurances_id option:selected").attr('data-id');
        if(type_of_insurance_text == 'Business') {
            $("#sub_type_of_insurance").show();
        }
        else {
            $("#sub_type_of_insurance").hide();
        }
        if(type_of_insurance_text == 'Car') {
            $("#car_fields").show();
        }
        else {
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
        columns: [
            { data: 'id', name: 'id', render:function(data, type, row){
                return "<a href='"+config.routes.customer_data_table_route+'/'+row.id +"'>" + row.id + "</a>"
            }},
            { data: "first_name", name: "first_name" },
            { data: "email", name: "email" },
            { data: "mobile_no", name: "mobile_no" },
            { data: "gender", name: "gender" },
            { data: "has_alfred_access", name: "has_alfred_access" },
            { data: "dob", name: "dob", orderable: false, searchable: false },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },
            // {
            //     data: "action",
            //     name: "action",
            //     orderable: false,
            //     searchable: false,
            // },
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

    dateRangePickerChange("", "");
    $(".applyBtn, .ranges li").click(function () {
        $(".loader").show();
        setTimeout(() => {
            var date = $("#reportrange span").html();
            var dateAsArray = date.split("-");
            var startDate = moment(dateAsArray[0]).format("YYYY-MM-DD");
            var endDate = moment(dateAsArray[1]).format("YYYY-MM-DD");
            dateRangePickerChange(startDate, endDate);
        }, 1000);
    });

    $("#return_to_view").click(function (e) {
        e.preventDefault();
        var input = '<input name="return_to_view" type="hidden" value="1"/>';
        $("#redirect_to_view_div").html(input);
        setTimeout(function () {
            $("form").submit();
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


});
$('#car_make_value').on('change',function(e) {
    console.log('m');
    var make_code = $("#car_make_value option:selected").attr('data-id');
    $.get('/valuation/car-models?make_code='+ make_code,function(data) {
        var carmodel = $('#car_model_value').empty();
        carmodel.append('<option value="">Select</option>');
        $.each(data,function(create,carmodelObj) {
            var option = $('<option/>', {id:create, value:carmodelObj});
            carmodel.append('<option value="'+carmodelObj.id+'">'+carmodelObj.text+'</option>');
        });
    });
});
$('#car_model_value').on('change',function(e) {
    console.log('md');
    var modelId = $("#car_model_value option:selected").val();
    $.get('/valuation/car-model-detail?modelId='+ modelId, function(data) {
        var cartrim = $('#car_trim_value').empty();
         cartrim.append('<option value="">Select</option>');
        if(data.length == 0){
            cartrim.append('<option value="">No Trim Available</option>');
            $('#car_trim_value option:eq(1)').prop('selected', true);
        }
        $.each(data,function(create,cartrimObj) {
            var option = $('<option/>', {id:create, value:cartrimObj});
            cartrim.append('<option value="'+cartrimObj.id+'">'+cartrimObj.text+'</option>');
        });
    });
});

$(document).on('click','.delete', function(){
    var route = $(this).attr('date-route');
    $('#delete-form').attr('action',route);
    $('#exampleModal').modal('show');

})
function dateRangePickerChange(startDate, endDate) {
    $.ajax({
        url: config.routes.load_dashboard_stats,
        method: "POST",
        data: { startDate, endDate, _token: config._token },
        success: function (data) {
            console.log("request " + data);
            $(".customer-count").html(data.totalCustomers);
            $(".carquote-count").html(data.totalCarQuotes);
            $(".duration").html(startDate + " - " + endDate);
            $(".loader").hide();
        },
    });
}
