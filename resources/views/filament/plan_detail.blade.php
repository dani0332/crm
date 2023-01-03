@php
$state = $getStatePath();
$data = $getRecord();
@endphp

@if ($state == 'mountedTableActionData.memberPremiumBreakdown')
@php
$members = json_decode($data->memberPremiumBreakdown, true) ?? [];
@endphp
<div>
  <table class="filament-tables-table w-full text-start divide-y table-auto text-sm">
    <thead>
      <tr>
        <th class="px-4 py-2 text-left">Name</th>
        <th class="px-4 py-2 text-left">Employee</th>
        <th class="px-4 py-2 text-left">DOB</th>
        <th class="px-4 py-2 text-left">Gender</th>
        <th class="px-4 py-2 text-left">Premium</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($members as $key => $member)
      <tr>
        <td class="px-4 py-2 text-left">Member {{ $key + 1 }}</td>
        <td class="px-4 py-2 text-left">{{ $member['memberCategoryText'] }}</td>
        <td class="px-4 py-2 text-left">{{ $member['dob'] }}</td>
        <td class="px-4 py-2 text-left">{{ $member['gender']}}</td>
        <td class="px-4 py-2 text-left">{{ $member['premium'] }}</td>
        @endforeach
    </tbody>
  </table>
</div>
@endif

@if ($state == 'mountedTableActionData.benefitsInpatient')
@php
$benefitsInclusion = json_decode($data->benefits, true)['inclusion'] ?? [];
@endphp
<div>
  <div class="flex flex-col gap-4 text-sm">
    @foreach ($benefitsInclusion as $key => $value)
    <div class="p-3 border rounded-lg">
      <h4 class="font-semibold mb-1">{{ ucwords($value['text']) }}</h4>
      <p>{{ $value['value'] }}</p>
    </div>
    @endforeach
  </div>
</div>
@endif

@if ($state == 'mountedTableActionData.benefitsOutpatient')
@php
$benefitsFeature = json_decode($data->benefits, true)['feature'] ?? [];
$benefitsInclusion = json_decode($data->benefits, true)['inclusion'] ?? [];
@endphp
<div>
  <div class="flex flex-col gap-4 text-sm">
    @foreach ($benefitsFeature as $key => $value)
    <div class="p-3 border rounded-lg">
      <h4 class="font-semibold mb-1">{{ ucwords($value['text']) }}</h4>
      <p>{{ $value['value'] }}</p>
    </div>
    @endforeach
    @foreach ($benefitsInclusion as $key => $value)
    <div class="p-3 border rounded-lg">
      <h4 class="font-semibold mb-1">{{ ucwords($value['text']) }}</h4>
      <p>{{ $value['value'] }}</p>
    </div>
    @endforeach
  </div>
</div>
@endif

@if ($state == 'mountedTableActionData.coInsurance')
@php
$coInsurance = json_decode($data->benefits, true)['coInsurance'] ?? [];
@endphp
<div>
  <div class="flex flex-col gap-4 text-sm">
    @foreach ($coInsurance as $key => $value)
    <div class="p-3 border rounded-lg">
      <h4 class="font-semibold mb-1">{{ ucwords($value['text']) }}</h4>
      <p>{{ $value['value'] }}</p>
    </div>
    @endforeach
  </div>
</div>
@endif

@if ($state == 'mountedTableActionData.regionCover')
@php
$regionCover = json_decode($data->benefits, true)['regionCover'] ?? [];
@endphp
<div>
  <div class="flex flex-col gap-4 text-sm">
    @foreach ($regionCover as $key => $value)
    <div class="p-3 border rounded-lg">
      <h4 class="font-semibold mb-1">{{ ucwords($value['text']) }}</h4>
      <p>{{ $value['value'] }}</p>
    </div>
    @endforeach
  </div>
</div>
@endif

@if ($state == 'mountedTableActionData.maternityCover')
@php
$maternityCover = json_decode($data->benefits, true)['maternityCover'] ?? [];
@endphp
<div>
  <div class="flex flex-col gap-4 text-sm">
    @foreach ($maternityCover as $key => $value)
    <div class="p-3 border rounded-lg">
      <h4 class="font-semibold mb-1">{{ ucwords($value['text']) }}</h4>
      <p>{{ $value['value'] }}</p>
    </div>
    @endforeach
  </div>
</div>
@endif

@if ($state == 'mountedTableActionData.benefitsExclusions')
@php
$exclusions = json_decode($data->benefits, true)['exclusion'] ?? [];
@endphp
<div>
  <div class="flex flex-col gap-4 text-sm">
    @foreach ($exclusions as $key => $value)
    <div class="p-3 border rounded-lg">
      <h4 class="font-semibold mb-1">{{ ucwords($value['text']) }}</h4>
      <p>{{ $value['value'] }}</p>
    </div>
    @endforeach
  </div>
</div>
@endif

@if ($state == 'mountedTableActionData.policyDetail')
@php
$networkLink = json_decode($data->benefits, true)['networkLink'] ?? [];
@endphp
<div>
  <div class="flex flex-col gap-4 text-sm">
    @foreach ($networkLink as $key => $value)
    <div class="p-3 border rounded-lg">
      <a href="{{ $value['value'] }}" target="_blank" class="font-semibold hover:text-primary-600">{{ ucwords($value['text']) }}</a>
    </div>
    @endforeach
  </div>
</div>
@endif