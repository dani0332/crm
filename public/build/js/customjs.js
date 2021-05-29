$(document).ready(function () {
    $("#datepicker").datepicker({ dateFormat: "yy-mm-dd" });
    $("#datepicker_2").datepicker({ dateFormat: "yy-mm-dd" });

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
            { data: "id", name: "id" },
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
            {
                data: "action",
                name: "action",
                orderable: false,
                searchable: false,
            },
        ],
    });

    var rewardCategory = $(".reward-category-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        ajax: config.routes.reward_categories_datatable_route,
        columns: [
            { data: "text", name: "text" },
            { data: "text_ar", name: "text_ar" },
            { data: "sort_order", name: "sort_order" },
            { data: "is_active", name: "is_active" },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },
            {
                data: "action",
                name: "action",
                orderable: false,
                searchable: false,
            },
        ],
    });

    var rewardTags = $(".reward-tag-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        ajax: config.routes.reward_tags_datatable_route,
        columns: [
            { data: "text", name: "text" },
            { data: "text_ar", name: "text_ar" },
            { data: "sort_order", name: "sort_order" },
            { data: "is_active", name: "is_active" },
            { data: "created_at", name: "created_at" },
            { data: "updated_at", name: "updated_at" },
            {
                data: "action",
                name: "action",
                orderable: false,
                searchable: false,
            },
        ],
    });

    $(".reward-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        ajax: config.routes.reward_datatable_route,
        columns: [
            { data: "id", name: "id" },
            { data: "partner_id", name: "partner_id" },
            { data: "discount", name: "discount" },
            { data: "start_date", name: "start_date" },
            { data: "end_date", name: "end_date" },
            { data: "is_active", name: "is_active" },
            { data: "is_flat_discount", name: "is_flat_discount" },
            {
                data: "action",
                name: "action",
                orderable: false,
                searchable: false,
            },
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
            { data: "id", name: "id" },
            { data: "name", name: "name" },
            { data: "email", name: "email" },
            {
                data: "action",
                name: "action",
                orderable: false,
                searchable: false,
            },
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
            { data: "id", name: "id" },
            { data: "name", name: "name" },
            {
                data: "action",
                name: "action",
                orderable: false,
                searchable: false,
            },
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
            { data: "id", name: "id" },
            { data: "car_value", name: "car_value" },
            { data: "is_synced", name: "is_synced" },
            { data: "device", name: "device" },
            { data: "code", name: "code" },
            {
                data: "action",
                name: "action",
                orderable: false,
                searchable: false,
            },
        ],
    });

    $("#search-car-quote").submit(function (e) {
        e.preventDefault();
        carquoteDatatable.draw();
    });

    $("#resubmit_api_carquote").click(function () {
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
            // {data: "checkbox", name: "checkbox",
            // render: function (data, type, row, meta) {
            //     return '<input type="checkbox" class="flat multicheckbox" name="row[]" data-id="'+row.id+'">';
            // }},
            { data: "id", name: "id" },
            { data: "preference", name: "preference" },
            { data: "is_synced", name: "is_synced" },
            { data: "device", name: "device" },
            { data: "code", name: "code" },
            {
                data: "action",
                name: "action",
                orderable: false,
                searchable: false,
            },
        ],
    });

    var customerDataTable = $(".customer-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: {
            url: config.routes.customer_data_table_route,
            data: function (d) {
                d.searchtype = $("input[name=searchtype]:checked").val();
                d.searchfield = $("input[name=searchfield]").val();
            },
        },
        columns: [
            // {data: "checkbox", name: "checkbox",
            // render: function (data, type, row, meta) {
            //     return '<input type="checkbox" class="flat multicheckbox" name="row[]" data-id="'+row.id+'">';
            // }},
            { data: "id", name: "id" },
            { data: "first_name", name: "first_name" },
            { data: "email", name: "email" },
            { data: "mobile_no", name: "mobile_no" },
            { data: "gender", name: "gender" },
            { data: "has_alfred_access", name: "has_alfred_access" },
            { data: "dob", name: "dob", orderable: false, searchable: false },
            {
                data: "action",
                name: "action",
                orderable: false,
                searchable: false,
            },
        ],
    });
    $("#search-customer").submit(function (e) {
        customerDataTable.draw();
        e.preventDefault();
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

    $(".applyBtn").click(function () {
        dateRangePickerChange();
    });

    $(".ranges li").click(function () {
        dateRangePickerChange();
    });

    function dateRangePickerChange() {
        setTimeout(() => {
            var date = $("#reportrange span").html();
            var dateAsArray = date.split("-");
            var startDate = moment(dateAsArray[0]).format("YYYY-MM-DD");
            var endDate = moment(dateAsArray[1]).format("YYYY-MM-DD");
            $.ajax({
                url: config.routes.load_dashboard_stats,
                method: "POST",
                data: { startDate, endDate, _token: config._token },
                success: function (data) {
                    console.log("request " + data);
                    $(".customer-count").html(data.totalCustomers);
                    $(".carquote-count").html(data.totalCarQuotes);
                    $(".duration").html(startDate + " - " + endDate);
                },
            });
        }, 1000);
    }

    $("#return_to_view").click(function (e) {
        e.preventDefault();
        var input = '<input name="return_to_view" type="hidden" value="1"/>';
        $("#redirect_to_view_div").html(input);
        setTimeout(function () {
            $("form").submit();
        }, 500);
    });
});
