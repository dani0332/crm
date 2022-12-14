@php
    use App\Enums\GenericRequestEnum;
@endphp
<form method="post" action="{{ route('members.update', $data->id) }}" autocomplete="off">
    {{ csrf_field() }}
    {{ method_field('PUT') }}
    <input type="hidden" name="health_quote_request_id" value="{{$data->health_quote_request_id}}" id="">
    <input type="hidden" name="modelType" value="Health" id="">
    <div class="modal-header">
        <h5 class="modal-title" id="duplicateLeadModalLabel" style="font-size: 16px !important;">
            <i class="fa fa-users" aria-hidden="true"></i>
            <strong style="margin-left: 13px;">Edit Member</strong>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <div class="col-md-12">
            <div class="col-md-6">
                <div class="input-group">
                <label class="col-form-label col-md-3 col-sm-3 label-align">Gender</label>
                    <select name="gender" id="" class="form-control">
                        <option value="">Please Select Gender</option>
                        <option value="{{GenericRequestEnum::MALE_SINGLE_VALUE}}" @if($data->gender == GenericRequestEnum::MALE_SINGLE_VALUE) selected @endif>{{GenericRequestEnum::MALE_SINGLE}}</option>
                        <option value="{{GenericRequestEnum::FEMALE_SINGLE_VALUE}}" @if($data->gender == GenericRequestEnum::FEMALE_SINGLE_VALUE) selected @endif>{{GenericRequestEnum::FEMALE_SINGLE}}</option>
                        <option value="{{GenericRequestEnum::FEMALE_MARRIED_VALUE}}" @if($data->gender == GenericRequestEnum::FEMALE_MARRIED_VALUE) selected @endif>{{GenericRequestEnum::FEMALE_MARRIED}}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align">DOB</label>
                    <input type="text" name="dob" class="form-control ebp_dob" title="DOB" value="{{\Carbon\Carbon::createFromTimestamp(strtotime($data->dob))->format('d-m-Y')}}"/>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="col-md-6">
                <div class="input-group">
                <label class="col-form-label col-md-3 col-sm-3 label-align">Category</label>
                    <select name="member_category" id="" class="form-control">
                        <option value="">Please Select Member Category</option>
                        @foreach($categories as $member)
                        <option value="{{$member->id}}"  @if($data->member_category_id == $member->id) selected @endif>{{$member->text}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                <label class="col-form-label col-md-3 col-sm-3 label-align">Salary Band</label>
                <select name="salary_band" id="" class="form-control">
                        <option value="">Please Select Salary Band</option>
                        @foreach($salaries as $salary)
                        <option value="{{$salary->id}}" @if($data->salary_band_id == $salary->id) selected @endif>{{$salary->text}}</option>
                        @endforeach
                </select>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer" style="justify-content: center;">
        <button type="submit" class="btn btn-sm btn-success">Save</button>
    </div>
</form>
<script>
    $(document).ready(function () {
        $('.ebp_dob').datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: 'dd-mm-yy',
            yearRange: '-80:+00',
        });
    });
</script>