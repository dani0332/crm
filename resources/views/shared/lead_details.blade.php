<meta name="csrf-token" content="{{ csrf_token() }}" />
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $('#update_lead_button').on('click', function (e) {
        var premium = $("#premium").val();
        var quote_status_id = $("#quote_status_id").val();
        if(premium == '' || quote_status_id == '') {
            alert('Please fill all the fields');
            return false;
        }
        e.preventDefault();
        $.ajax({
            url: "{{ url('/UpdateLeadManualProcess') }}",
            type: 'post',
            data: {
                lead_id: $('#lead_id').val(),
                premium: premium,
                quote_status_id: quote_status_id,
                is_create: $('#is_create').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function(result) {
                // $('#car_plan_manual_process_text').show();
                // $("#car_plan_manual_process_text").text(result);
                // $('#car_plan_manual_process_text').hide(5000);
                // location.reload();
            }
        });
    });
</script>
<div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            <div class="x_panel" style="border: none">
                <div class="x_title" style="text-align: center;">
                    <div class="h5"></div>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">

                    <div class="tab-content" style="padding-top: 20px;">
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <form id="update_lead_details" method='post'  data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                                    {{csrf_field()}}
                                    <input type="hidden" id="lead_id" name="lead_id" value="{{ $entity->id }}">
                                    <input type="hidden" id="is_create" name="is_create" value="0">
                                    <table cellpadding="3" cellspacing="3">
                                    <tr><td>CBD ID:</td> 
                                    <td>
                                        <input type="text" readonly id="code" name="code" value="{{ old('code', $entity->code) }}" class="form-control">
                                    </td></tr>
                                    <tr><td>Premium:</td> 
                                    <td>
                                        <input type="number" id="premium" name="premium" value="{{ old('premium', $entity->premium) }}" class="form-control" onKeyDown="if(this.value.length==8) return false;">
                                    </td></tr>
                                    <tr><td>Lead Status:</td> 
                                    <td>
                                        <select class="form-control" id='quote_status_id' name="quote_status_id">
                                            @if($drop_down_list)
                                                @foreach($drop_down_list as $drop_down)
                                                <option value="{{$drop_down->id}}" {{ $drop_down->id == $entity->quote_status_id ? 'selected="selected"' : '' }}>{{$drop_down->text}}</option>
                                                @endforeach
                                            @endif 
                                        </select>
                                    </td>
                                    </tr>
                                    <tr>
                                        <td /> <td /> <td />
                                        <td align="right"><button type="submit" class="btn btn-warning btn-sm" id="update_lead_button">Update</button></td>
                                    </tr>
                                </table>
                            </form>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>


