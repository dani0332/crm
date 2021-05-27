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

    var carqouteDatatable = $(".carqoute-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: {
            url: config.routes.carqoute_datatable_route,
            data: function (d) {
                d.searchtype = $("input[name=searchtype]:checked").val();
                d.searchfield = $("input[name=searchfield]").val();
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

    $("#search-car-qoute").submit(function (e) {
        carqouteDatatable.draw();
        e.preventDefault();
    });

    $("#resubmit_api_carqoute").click(function () {
        $("#resubmit_api_carqoute").attr("disabled", true);
        var data_id = $(this).attr("data-id");
        var carQoutes = [];
        if (data_id != "") carQoutes.push(data_id);
        $(".multicheckbox").each(function (index) {
            if ($(this).prop("checked")) {
                var id = $(this).attr("data-id");
                carQoutes.push(id);
            }
        });
        if (carQoutes.length > 0) {
            $.ajax({
                url: config.routes.carqoute_resubmitap_route,
                type: "post",
                data: { car_qoutes: carQoutes, _token: config._token },
                success: function (response) {
                    $("#success_message").show();
                    $("#success_message")
                        .fadeIn()
                        .html("Resubmit Api Execution is successfull");

                    $(".carqoute-data-table").DataTable().ajax.reload();

                    setTimeout(function () {
                        $("#resubmit_api_carqoute").attr("disabled", false);
                        $("#success_message").fadeOut("slow");
                    }, 3000);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                    $("#error_message").show();
                    $("#error_message").fadeIn().html(errorThrown);
                    setTimeout(function () {
                        $("#resubmit_api_carqoute").attr("disabled", false);
                        $("#error_message").fadeOut("slow");
                    }, 3000);
                },
            });
        } else {
            $("#error_message").show();
            $("#error_message").fadeIn().html("Please select atleast one row");
            setTimeout(function () {
                $("#resubmit_api_carqoute").attr("disabled", false);
                $("#error_message").fadeOut("slow");
            }, 3000);
        }
    });

    $(".healthqoute-data-table").DataTable({
        ordering: false,
        info: false,
        searching: false,
        bLengthChange: false,
        serverSide: true,
        ajax: config.routes.healthqoute_datatable_route,
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


    $('.auditablebtn').click(function(){
        var auditableId = $(this).attr('data-id');
        var auditableType = $(this).attr('data-model');
        $(this).attr('disabled',true);

        $.ajax({
            url:config.routes.load_auditable,
            method:"POST",
            data:{auditableId,auditableType,_token:config._token},
            success:function(data){
                $('#auditable').html(data);
                $('.auditablebtn').hide();
            }
        })
    })
});
